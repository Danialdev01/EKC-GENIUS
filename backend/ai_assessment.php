<?php
/**
 * Regenerate the AI Development Profile for a single student + month.
 *
 * Behavior:
 *   - Reads the current-month scores + previous 6 months of scores.
 *   - Calls callAI() to produce a Strengths / Focus Area / Trend Analysis.
 *   - Soft-deletes the prior active row (status=0) and inserts a fresh active row.
 *   - On API failure: does NOT write to the DB. Returns ['error' => 'api_failed', 'message' => …]
 *     so callers can show a "click to retry" UI instead of a placeholder.
 *
 * Returns:
 *   - null                                           — no current-month scores (bail-early; DB untouched).
 *   - ['error' => 'api_failed', 'message' => '…']   — AI call failed; DB untouched.
 *   - array on success, with DB-style keys
 *     (ai_assessment_strengths, ai_assessment_focus_area, ai_assessment_trend_analysis,
 *      ai_assessment_month, ai_assessment_year).
 */
function regenerateAiAssessment(PDO $pdo, int $studentId, int $month, int $year): ?array
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM student_assessments
        WHERE student_id = ? AND student_assessment_month = ? AND student_assessment_year = ? AND student_assessment_status = 1
    ");
    $stmt->execute([$studentId, $month, $year]);
    if ((int)$stmt->fetchColumn() === 0) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT sa.assessment_id, a.assessment_title, sa.student_assessment_value
        FROM student_assessments sa
        INNER JOIN assessments a ON a.assessment_id = sa.assessment_id
        WHERE sa.student_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1
    ");
    $stmt->execute([$studentId, $month, $year]);
    $currentScores = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $currentScores[] = $row['assessment_title'] . ': ' . $row['student_assessment_value'];
    }

    $previousDataStr = '';
    for ($i = 1; $i <= 6; $i++) {
        $m = $month - $i;
        $y = $year;
        while ($m < 1) {
            $m += 12;
            $y -= 1;
        }

        $stmt = $pdo->prepare("
            SELECT sa.assessment_id, a.assessment_title, sa.student_assessment_value
            FROM student_assessments sa
            INNER JOIN assessments a ON a.assessment_id = sa.assessment_id
            WHERE sa.student_id = ? AND sa.student_assessment_month = ? AND sa.student_assessment_year = ? AND sa.student_assessment_status = 1
        ");
        $stmt->execute([$studentId, $m, $y]);
        $scoresData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($scoresData)) {
            $monthLabel = date('F Y', mktime(0, 0, 0, $m, 1, $y));
            $parts = [];
            foreach ($scoresData as $row) {
                $parts[] = $row['assessment_title'] . ': ' . $row['student_assessment_value'];
            }
            $previousDataStr .= $monthLabel . " Scores: " . implode(", ", $parts) . "\n";
        }
    }

    $stmt = $pdo->prepare("SELECT student_name FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $studentName = $stmt->fetchColumn() ?: 'Student';

    $prompt = "You are an educational specialist for special needs children. Analyze the following assessment data for student {$studentName}.

Current Month (" . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ") Scores:
" . implode(", ", $currentScores) . "

Previous Months Data:
" . ($previousDataStr === '' ? "No previous data available" : $previousDataStr) . "

Please provide your analysis in exactly this format. Use flowing sentences of plain prose, not lists:

Strengths: [2 to 3 sentences of plain text describing the top 3 areas where the student performs well.]
Focus Area: [2 to 3 sentences of plain text describing the top 3 areas that need improvement.]
Trend Analysis: [2 to 3 sentences of plain text analyzing the student's current assessment pattern and how it has changed over the previous months.]

STRICT FORMATTING RULES — these are not optional:
- Write in plain prose only. Use sentences separated by commas or periods.
- Do NOT use bullet points, numbered lists, or any list-like formatting.
- Do NOT use any markdown characters: no asterisks (*), no hashtags (#), no slashes (/), no underscores (_), no backticks (`), no greater-than signs (>), no tildes (~).
- Do NOT use any HTML tags.
- Do NOT use headings, bold, italic, code blocks, or blockquotes.
- Just plain sentences. That is all.";

    $apiKey = trim((string)(getenv('OPENROUTER_API_KEY') ?: ($_ENV['OPENROUTER_API_KEY'] ?? '')));
    require_once __DIR__ . '/ai.php';

    $aiSuccess = false;
    $aiErrorMsg = 'AI analysis is not available.';
    $aiStrengths     = '';
    $aiFocusArea     = '';
    $aiTrendAnalysis = '';

    /**
     * Defensive post-processing: strip leftover markdown/HTML markers from an AI
     * section. The prompt forbids them, but the model sometimes slips — better
     * to scrub the parsed text than trust the prompt alone.
     *   - removes leading "- ", "* ", "1. ", "1) " etc. from each line
     *   - removes leading "#" header markers from each line
     *   - removes inline emphasis chars (*, _, `, ~, >) and HTML tag brackets
     */
    $cleanSection = function (string $text): string {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $out = [];
        foreach ($lines as $line) {
            $line = preg_replace('/^[\s>\-*_`~#]+/', '', $line);
            $line = preg_replace('/^\d+[.)]\s+/', '', $line);
            $out[] = $line;
        }
        $text = implode("\n", $out);
        $text = preg_replace('/[*_`~]/', '', $text);
        $text = preg_replace('/<\/?[a-zA-Z][^>]*>/', '', $text);
        return trim($text);
    };

    if (function_exists('callAI') && $apiKey) {
        try {
            $result = callAI($prompt, 'openai/gpt-4o-mini', $apiKey);
            if (isset($result['success']) && $result['success']) {
                $aiSuccess = true;
                $content = $result['content'];
                if (preg_match('/Strengths:(.*?)(?=Focus Area:|$)/s', $content, $m)) {
                    $aiStrengths = $cleanSection($m[1]);
                }
                if (preg_match('/Focus Area:(.*?)(?=Trend Analysis:|$)/s', $content, $m)) {
                    $aiFocusArea = $cleanSection($m[1]);
                }
                if (preg_match('/Trend Analysis:(.*)/s', $content, $m)) {
                    $aiTrendAnalysis = $cleanSection($m[1]);
                }
            } else {
                $aiErrorMsg = $result['error'] ?? 'AI call returned no content.';
            }
        } catch (Exception $e) {
            $aiErrorMsg = $e->getMessage();
        }
    } else {
        $aiErrorMsg = 'OPENROUTER_API_KEY is not configured.';
    }

    if (!$aiSuccess) {
        return ['error' => 'api_failed', 'message' => $aiErrorMsg];
    }

    $stmt = $pdo->prepare("UPDATE ai_assessments SET ai_assessment_status = 0 WHERE student_id = ? AND ai_assessment_month = ? AND ai_assessment_year = ?");
    $stmt->execute([$studentId, $month, $year]);

    $stmt = $pdo->prepare("
        INSERT INTO ai_assessments (student_id, ai_assessment_strengths, ai_assessment_focus_area, ai_assessment_trend_analysis, ai_assessment_month, ai_assessment_year, ai_assessment_status, ai_assessment_created_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$studentId, $aiStrengths, $aiFocusArea, $aiTrendAnalysis, $month, $year]);

    return [
        'ai_assessment_strengths'      => $aiStrengths,
        'ai_assessment_focus_area'     => $aiFocusArea,
        'ai_assessment_trend_analysis' => $aiTrendAnalysis,
        'ai_assessment_month'          => $month,
        'ai_assessment_year'           => $year,
    ];
}
