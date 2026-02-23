<?php
/**
 * Temporary test page to debug PDF download filename issue.
 * Self-deletes after use. Token-protected.
 */
if (!isset($_GET['token']) || $_GET['token'] !== 'ah_ho_deploy_2026') {
    http_response_code(403);
    exit('Forbidden');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'page';

if ($action === 'prepare') {
    // Simulate the ajax_prepare_pdf flow: create a temp file and return URL
    $upload_dir = __DIR__ . '/wp-content/uploads/ah-ho-invoicing/temp/';
    $upload_url = 'https://ahhofruit.com/wp-content/uploads/ah-ho-invoicing/temp/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Create .htaccess if missing
    $htaccess = $upload_dir . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess,
            "Options -Indexes\n" .
            "<FilesMatch \"\\.pdf$\">\n" .
            "    ForceType application/octet-stream\n" .
            "    Header set Content-Disposition attachment\n" .
            "</FilesMatch>\n"
        );
    }

    // Create a small test PDF
    $pdf_content = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n206\n%%EOF";

    $token = substr(md5(time()), 0, 8);
    $filename = "test-invoice-4275-{$token}.pdf";
    $filepath = $upload_dir . $filename;
    file_put_contents($filepath, $pdf_content);

    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => true,
        'data' => array(
            'url' => $upload_url . $filename,
            'filename' => 'test-invoice-4275.pdf',
            'temp_dir_exists' => is_dir($upload_dir),
            'file_exists' => file_exists($filepath),
            'file_size' => filesize($filepath),
        )
    ));
    exit;
}

// Default: show test page
?>
<!DOCTYPE html>
<html>
<head><title>PDF Download Test</title></head>
<body>
<h1>PDF Download Filename Test</h1>
<p>This tests whether static files from /uploads/ download with correct filenames.</p>

<h2>Test 1: Direct static file link</h2>
<button id="btn1" onclick="testPrepareAndDownload()">Generate &amp; Download (window.location)</button>
<span id="status1"></span>

<h2>Test 2: Direct link (after prepare)</h2>
<div id="directLink" style="display:none">
    <a id="directA" href="#">Download direct link</a>
</div>

<h2>Log:</h2>
<pre id="log"></pre>

<script>
function log(msg) {
    document.getElementById('log').textContent += msg + '\n';
}

function testPrepareAndDownload() {
    var btn = document.getElementById('btn1');
    btn.textContent = 'Generating...';
    btn.disabled = true;
    document.getElementById('status1').textContent = '';

    var url = window.location.href.split('?')[0] + '?token=ah_ho_deploy_2026&action=prepare';
    log('Fetching: ' + url);

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.responseType = 'json';

    xhr.onload = function() {
        btn.textContent = 'Generate & Download (window.location)';
        btn.disabled = false;

        log('Status: ' + xhr.status);
        log('Response: ' + JSON.stringify(xhr.response));

        var r = xhr.response;
        if (xhr.status === 200 && r && r.success && r.data && r.data.url) {
            log('SUCCESS - URL: ' + r.data.url);
            document.getElementById('status1').textContent = 'Redirecting to: ' + r.data.url;

            // Show direct link too
            var directA = document.getElementById('directA');
            directA.href = r.data.url;
            directA.textContent = 'Direct: ' + r.data.url;
            document.getElementById('directLink').style.display = 'block';

            // Trigger download via window.location
            window.location.href = r.data.url;
        } else {
            var msg = (r && r.data && r.data.error) ? r.data.error : 'Unknown error';
            log('FAILED: ' + msg);
            document.getElementById('status1').textContent = 'FAILED: ' + msg;
        }
    };

    xhr.onerror = function() {
        btn.textContent = 'Generate & Download (window.location)';
        btn.disabled = false;
        log('XHR Error');
    };

    xhr.send();
}
</script>
</body>
</html>
<?php
