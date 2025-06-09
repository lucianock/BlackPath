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
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .tech-item {
            background: #f3f4f6;
            padding: 8px;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f3f4f6;
            font-weight: bold;
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

    @if(isset($results['whatweb']))
    <div class="section">
        <div class="section-title">Website Technologies</div>
        @php
            $whatwebOutput = $results['whatweb'][0]->raw_output;
            $technologies = [];
            
            // Try to parse as JSON first
            try {
                $jsonData = json_decode($whatwebOutput, true);
                if (is_array($jsonData)) {
                    foreach ($jsonData as $target) {
                        if (isset($target['plugins'])) {
                            foreach ($target['plugins'] as $plugin => $data) {
                                if ($plugin !== 'Title' && $plugin !== 'IP') {
                                    $version = isset($data['version']) ? " " . $data['version'] : "";
                                    $technologies[] = $plugin . $version;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fallback to regex parsing if JSON fails
                preg_match_all('/\[(.*?)\]/', $whatwebOutput, $matches);
                foreach ($matches[1] as $match) {
                    if (strpos($match, 'Title') === false && strpos($match, 'IP') === false) {
                        $parts = explode('[', $match);
                        foreach ($parts as $part) {
                            if (!empty($part)) {
                                $tech = trim(str_replace(']', '', $part));
                                if ($tech) $technologies[] = $tech;
                            }
                        }
                    }
                }
            }
            
            $technologies = array_unique($technologies);
        @endphp
        
        <div class="tech-grid">
            @foreach($technologies as $tech)
            <div class="tech-item">{{ $tech }}</div>
            @endforeach
        </div>
    </div>
    @endif

    @if(isset($results['nmap']))
    <div class="section">
        <div class="section-title">Security Overview</div>
        @php
            $nmapOutput = $results['nmap'][0]->raw_output;
            preg_match_all('/(\d+)\/tcp\s+(\w+)\s+(\w+)\s+(.*)/', $nmapOutput, $matches);
            $ports = [];
            for ($i = 0; $i < count($matches[0]); $i++) {
                $ports[] = [
                    'number' => $matches[1][$i],
                    'state' => $matches[2][$i],
                    'service' => $matches[3][$i],
                    'version' => $matches[4][$i]
                ];
            }
        @endphp

        <table>
            <thead>
                <tr>
                    <th>Port</th>
                    <th>State</th>
                    <th>Service</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ports as $port)
                <tr>
                    <td>{{ $port['number'] }}/tcp</td>
                    <td>{{ $port['state'] }}</td>
                    <td>{{ $port['service'] }}</td>
                    <td>{{ $port['version'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
                /{{ $resource['path'] }} (Status: {{ $resource['status'] }})
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
            <li>Keep all discovered technologies up to date with security patches</li>
            <li>Review and secure all discovered resources and open ports</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
        <p>This report was generated automatically. For professional security assessment, please consult with cybersecurity experts.</p>
    </div>
</body>
</html> 