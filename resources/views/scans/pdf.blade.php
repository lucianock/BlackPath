<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .section-title {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #4a5568;
        }
        .resource-list {
            list-style-type: none;
            padding-left: 0;
        }
        .resource-item {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed {
            background: #c6f6d5;
            color: #2f855a;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Security Scan Report</h1>
        <p>Generated on {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Scan Information</div>
        <div class="info-row">
            <span class="info-label">Domain:</span>
            {{ parse_url($scan->domain, PHP_URL_HOST) }}
        </div>
        <div class="info-row">
            <span class="info-label">Started:</span>
            {{ $scan->started_at }}
        </div>
        <div class="info-row">
            <span class="info-label">Completed:</span>
            {{ $scan->finished_at ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="status-badge status-{{ $scan->status }}">
                {{ ucfirst($scan->status) }}
            </span>
        </div>
    </div>

    @if(isset($results['nmap']))
    <div class="section">
        <div class="section-title">Security Overview</div>
        @php
            $nmapOutput = $results['nmap'][0]->raw_output;
            $isSecure = !str_contains(strtolower($nmapOutput), 'vulnerable');
        @endphp
        <div class="info-row">
            <strong>Security Status:</strong>
            {{ $isSecure ? 'No Critical Issues Found' : 'Some Attention Required' }}
        </div>
        <p>Basic security scan completed for {{ parse_url($scan->domain, PHP_URL_HOST) }}</p>
    </div>
    @endif

    @if(isset($results['gobuster']))
    <div class="section">
        <div class="section-title">Discovered Resources</div>
        @php
            $gobusterOutput = is_array($results['gobuster'][0]->raw_output) ? implode("\n", $results['gobuster'][0]->raw_output) : $results['gobuster'][0]->raw_output;
            preg_match_all('/\/([\w-]+(?:\.[\w-]+)*)\s+\(Status: (\d+)\)/', $gobusterOutput, $matches);
            $resources = [];
            foreach ($matches[1] as $index => $path) {
                if ($path !== 'favicon.ico') {
                    $resources[] = [
                        'path' => $path,
                        'status' => $matches[2][$index]
                    ];
                }
            }
        @endphp
        
        @if(count($resources) > 0)
        <ul class="resource-list">
            @foreach($resources as $resource)
            <li class="resource-item">
                /{{ $resource['path'] }}
            </li>
            @endforeach
        </ul>
        @else
        <p>No additional resources were discovered during the scan.</p>
        @endif
    </div>
    @endif

    <div class="section">
        <div class="section-title">Recommendations</div>
        <ul>
            <li>Regularly update your web server and its components</li>
            <li>Implement proper access controls for sensitive resources</li>
            <li>Monitor server logs for suspicious activities</li>
            <li>Consider implementing a Web Application Firewall (WAF)</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
        <p>This report was generated automatically. For professional security assessment, please consult with cybersecurity experts.</p>
    </div>
</body>
</html> 