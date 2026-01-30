<?php
require __DIR__ . '/../src/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Debug Scan Form</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
        }

        .box {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }

        button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>Debug Scan Data Flow</h1>

    <div class="box">
        <h2>1. Check Current Session</h2>
        <button onclick="checkSession()">Check Session</button>
        <div id="sessionResult"></div>
    </div>

    <div class="box">
        <h2>2. Simulate Save Scan</h2>
        <button onclick="simulateSave()">Save Test Data</button>
        <div id="saveResult"></div>
    </div>

    <div class="box">
        <h2>3. Fetch Scan Data</h2>
        <button onclick="fetchData()">Fetch from get-scan.php</button>
        <div id="fetchResult"></div>
    </div>

    <div class="box">
        <h2>4. Clear Scan Data</h2>
        <button onclick="clearData()">Clear Data</button>
        <div id="clearResult"></div>
    </div>

    <script>
        async function checkSession() {
            const result = document.getElementById('sessionResult');
            try {
                const response = await fetch('./api/get-scan.php');
                const data = await response.json();
                result.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                result.innerHTML = '<pre>Error: ' + error + '</pre>';
            }
        }

        async function simulateSave() {
            const result = document.getElementById('saveResult');
            try {
                const response = await fetch('./api/save-scan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        member_id: 1,
                        member_name: 'Test User',
                        book_id: 1,
                        book_title: 'Test Book'
                    })
                });
                const data = await response.json();
                result.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                result.innerHTML = '<pre>Error: ' + error + '</pre>';
            }
        }

        async function fetchData() {
            const result = document.getElementById('fetchResult');
            try {
                const response = await fetch('./api/get-scan.php');
                const data = await response.json();
                result.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                result.innerHTML = '<pre>Error: ' + error + '</pre>';
            }
        }

        async function clearData() {
            const result = document.getElementById('clearResult');
            try {
                const response = await fetch('./api/clear-scan.php');
                const data = await response.json();
                result.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                result.innerHTML = '<pre>Error: ' + error + '</pre>';
            }
        }
    </script>
</body>

</html>