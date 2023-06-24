<?php
$dnsTypes = ["A", "AAAA", "CNAME", "MX", "NS", "PTR", "SRV", "TXT"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $domains = $_POST["domain"];

    if (!empty($domains)) {
        $domains = explode("\n", str_replace("\r", "", $domains));

        // Open the CSV file
        // Make sure that the file exists in the directory first, and ensure it has rw access
        $file = fopen('dnsrecords.csv', 'w');

        // Write the CSV header
        fputcsv($file, array_merge(['Domain'], $dnsTypes));

        foreach ($domains as $domain) {
            $allRecords = [];
            foreach ($dnsTypes as $type) {
                $dnsRecords = dns_get_record($domain, constant("DNS_".$type));
                if (!empty($dnsRecords)) {
                    foreach ($dnsRecords as $record) {
                        switch ($type) {
                            case 'A':
                            case 'AAAA':
                                $allRecords[$type][] = $record['ip'];
                                break;
                            case 'NS':
                            case 'MX':
                            case 'CNAME':
                            case 'PTR':
                                $allRecords[$type][] = $record['target'];
                                break;
                            case 'SRV':
                                $allRecords[$type][] = $record['target'].':'.$record['port'];
                                break;
                            case 'TXT':
                                $allRecords[$type][] = $record['txt'];
                                break;
                        }
                    }
                }
            }

            // Determine the maximum count of any record type for this domain
            $maxCount = max(array_map('count', $allRecords));

            // Write the CSV rows
            for ($i = 0; $i < $maxCount; $i++) {
                $outputRow = [$domain];

                foreach ($dnsTypes as $type) {
                    if (isset($allRecords[$type][$i])) {
                        $outputRow[] = $allRecords[$type][$i];
                    } else {
                        $outputRow[] = "";
                    }
                }

                fputcsv($file, $outputRow);
            }

            // Add an empty line after each domain
            fputcsv($file, []);
        }

        // Close the CSV file
        fclose($file);

        // Echo the JavaScript to initiate the download of the CSV file
        echo '<script type="text/javascript">
                window.location.href = "dnsrecords.csv";
              </script>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            background-color: #1E555C;
            color: #EDB183;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        form {
            display: flex;
            flex-direction: column;
            width: 40%; /* Make form narrower */
        }

        label, textarea, input {
            margin-bottom: 1rem;
        }

        textarea {
            padding: 0.5rem;
            background-color: #EDB183;
            color: #1E555C;
        }

        input[type="submit"] {
            padding: 0.5rem;
            background-color: #EDB183;
            color: #1E555C;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #ffa052;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>DNS records to CSV</h1>
        <p>Currently supports A, AAAA, CNAME, MX, NS, PTR, SRV, TXT records. Enter one domain per line.</p>
        <form method="post" action="https://[DOMAIN NAME]/dns.php">
            <label for="domain">Domains (one per line):</label>
            <textarea id="domain" name="domain" rows="4" cols="50"></textarea>
            <input type="submit" value="Submit">
        </form>
    </body>
</html>
