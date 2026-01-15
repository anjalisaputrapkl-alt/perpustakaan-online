<?php
// Quick test untuk reports-filter.php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Filter API</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0284c7; }
    </style>
</head>
<body>
    <h1>Test Filter API</h1>
    <button onclick="testFilter()">Test Filter (tanpa parameter)</button>
    <button onclick="testFilterWithDates()">Test Filter (dengan tanggal)</button>
    <hr>
    <h3>Response:</h3>
    <pre id="response">Click button untuk test...</pre>

    <script>
    function testFilter() {
        fetch('./reports-filter.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('response').innerText = JSON.stringify(data, null, 2);
                console.log('✅ Filter Success!', data);
            })
            .catch(err => {
                document.getElementById('response').innerText = 'ERROR: ' + err.message;
                console.error('❌ Error:', err);
            });
    }

    function testFilterWithDates() {
        const start = '2024-01-01';
        const end = '2024-12-31';
        const url = `./reports-filter.php?start_date=${start}&end_date=${end}`;
        
        fetch(url)
            .then(r => r.json())
            .then(data => {
                document.getElementById('response').innerText = JSON.stringify(data, null, 2);
                console.log('✅ Filter Success!', data);
            })
            .catch(err => {
                document.getElementById('response').innerText = 'ERROR: ' + err.message;
                console.error('❌ Error:', err);
            });
    }
    </script>
</body>
</html>
