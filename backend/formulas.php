<?php

function calculateAverageScore($scores) {
    if (empty($scores)) return null;
    $scoreValues = array_values($scores);
    $scoreValues = array_filter($scoreValues, function($v) { return is_numeric($v); });
    if (empty($scoreValues)) return null;
    return round(array_sum($scoreValues) / count($scoreValues), 1);
}

function getScoreInterpretation($score) {
    if ($score >= 4.5) return ['label' => 'Excellent', 'class' => 'bg-emerald-100 text-emerald-700', 'emoji' => '🌟'];
    if ($score >= 3.5) return ['label' => 'Good', 'class' => 'bg-blue-100 text-blue-700', 'emoji' => '✅'];
    if ($score >= 2.5) return ['label' => 'Developing', 'class' => 'bg-amber-100 text-amber-700', 'emoji' => '🔔'];
    if ($score >= 1.5) return ['label' => 'Emerging', 'class' => 'bg-orange-100 text-orange-700', 'emoji' => '⚠️'];
    if ($score >= 1.0) return ['label' => 'Very Low', 'class' => 'bg-red-100 text-red-700', 'emoji' => '🚨'];
    return ['label' => 'No Data', 'class' => 'bg-slate-100 text-slate-500', 'emoji' => '—'];
}

function calculateAnnualIndex($monthlyAverages) {
    $validAverages = array_filter($monthlyAverages, function($v) { return $v !== null && is_numeric($v); });
    if (empty($validAverages)) return null;
    return round(array_sum($validAverages) / count($validAverages), 2);
}

function detectWeakAreas($scores, $threshold = 2.5) {
    $weakAreas = [];
    foreach ($scores as $assessmentId => $score) {
        if (is_numeric($score) && $score < $threshold) {
            $weakAreas[] = $assessmentId;
        }
    }
    return $weakAreas;
}

function calculateGrowthRate($scoreStart, $scoreEnd) {
    if (!is_numeric($scoreStart) || !is_numeric($scoreEnd) || $scoreStart == 0) return null;
    return round((($scoreEnd - $scoreStart) / $scoreStart) * 100, 1);
}

function calculateTrend($currentScore, $previousScore) {
    if (!is_numeric($currentScore) || !is_numeric($previousScore)) return null;
    $diff = round($currentScore - $previousScore, 1);
    
    if ($diff > 0.5) return ['label' => 'Improvement', 'class' => 'text-emerald-600', 'icon' => '↑'];
    if ($diff > 0.1) return ['label' => 'Slight Improvement', 'class' => 'text-emerald-400', 'icon' => '↗'];
    if ($diff == 0) return ['label' => 'Stable', 'class' => 'text-slate-500', 'icon' => '→'];
    return ['label' => 'Declining', 'class' => 'text-red-500', 'icon' => '↓'];
}

function calculateSpiderwebVector($scores, $totalCriteria = 8) {
    $vector = [];
    for ($i = 1; $i <= $totalCriteria; $i++) {
        $vector[] = $scores[$i] ?? 0;
    }
    return $vector;
}

function calculateAverageGrowth($monthlyScores) {
    if (count($monthlyScores) < 2) return 0;
    
    $growths = [];
    for ($i = 1; $i < count($monthlyScores); $i++) {
        if (isset($monthlyScores[$i]['avg']) && isset($monthlyScores[$i-1]['avg'])) {
            $growth = $monthlyScores[$i]['avg'] - $monthlyScores[$i-1]['avg'];
            $growths[] = $growth;
        }
    }
    
    if (empty($growths)) return 0;
    return round(array_sum($growths) / count($growths), 2);
}

function predictFutureScore($currentScore, $averageGrowth, $monthsAhead = 3) {
    if (!is_numeric($currentScore)) return null;
    $predicted = $currentScore + ($averageGrowth * $monthsAhead);
    return round(min(max($predicted, 0), 5), 1);
}

function getHistoricalMonthlyAverages($pdo, $studentId, $months = 12) {
    $monthlyAverages = [];
    
    for ($i = 0; $i < $months; $i++) {
        $m = date('n', strtotime("-$i months"));
        $y = date('Y', strtotime("-$i months"));
        
        $stmt = $pdo->prepare("
            SELECT AVG(student_assessment_value) as avg 
            FROM student_assessments 
            WHERE student_id = ? AND student_assessment_month = ? AND student_assessment_year = ? AND student_assessment_status = 1
        ");
        $stmt->execute([$studentId, $m, $y]);
        $avg = $stmt->fetchColumn();
        
        $monthlyAverages[] = [
            'month' => $m,
            'year' => $y,
            'label' => date('F Y', strtotime("-$i months")),
            'avg' => $avg ? round($avg, 1) : null
        ];
    }
    
    return $monthlyAverages;
}

function getAssessmentTitles($pdo) {
    $stmt = $pdo->prepare("SELECT assessment_id, assessment_title, assessment_icon FROM assessments WHERE assessment_status = 1 ORDER BY assessment_id");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}