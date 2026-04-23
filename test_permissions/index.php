<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Permission Tests</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Database Permission Tests</h1>
        
        <div class="grid gap-4 mb-8">
            <button onclick="runTest('setup.php')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
                1. Setup Test Table
            </button>
            
            <button onclick="runTest('test_basic.php')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
                2. Basic Permissions (SELECT, DESCRIBE, SHOW)
            </button>
            
            <button onclick="runTest('test_crud.php')" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
                3. CRUD Permissions (INSERT, UPDATE, DELETE)
            </button>
            
            <button onclick="runTest('test_all_tables.php')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
                4. All Tables CRUD Test
            </button>
            
            <button onclick="runAllTests()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
                5. Run All Tests
            </button>
        </div>
        
        <div id="results" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <p class="text-slate-500 text-center">Click a button above to run tests</p>
        </div>
    </div>
    
    <script>
        async function runTest(testFile) {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<p class="text-slate-500">Loading...</p>';
            
            try {
                const response = await fetch(testFile);
                const data = await response.json();
                
                let html = '<pre class="text-sm overflow-x-auto">' + JSON.stringify(data, null, 2) + '</pre>';
                
                let hasFail = false;
                if (data.results) {
                    Object.values(data.results).forEach(r => {
                        if (r.status === 'fail') hasFail = true;
                    });
                }
                
                resultsDiv.innerHTML = html;
                resultsDiv.className = 'bg-white rounded-2xl border shadow-sm p-6 ' + (hasFail ? 'border-red-300' : 'border-emerald-300');
            } catch (error) {
                resultsDiv.innerHTML = '<p class="text-red-600">Error: ' + error.message + '</p>';
                resultsDiv.className = 'bg-white rounded-2xl border border-red-300 shadow-sm p-6';
            }
        }
        
        async function runAllTests() {
            await runTest('setup.php');
            await runTest('test_basic.php');
            await runTest('test_crud.php');
            await runTest('test_all_tables.php');
        }
    </script>
</body>
</html>