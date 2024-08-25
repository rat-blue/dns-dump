<?php
$dnsTypes = ["A", "AAAA", "CNAME", "MX", "NS", "PTR", "SRV", "TXT"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $domains = $_POST["domain"];

    if (!empty($domains)) {
        $domains = explode("\n", str_replace("\r", "", $domains));

        // open the CSV file
        $file = fopen('dnsrecords.csv', 'w');

        // write the CSV header
        fputcsv($file, array_merge(['Domain'], $dnsTypes));

        foreach ($domains as $domain) {

            if (trim($domain) == '') {
                continue;
            }

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

            // determine maximum count of any record type for this domain
            $maxCount = max(array_map('count', $allRecords));

            // write the CSV rows
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

            // add an empty line after each domain
            fputcsv($file, []);
        }

        // close the CSV file
        fclose($file);

        // echo the JavaScript to initiate the download of the CSV file
        echo '<script type="text/javascript">
                window.location.href = "dnsrecords.csv";
              </script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon.png">
    <title>DNS to CSV</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
            color: #FFFFFF;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #000000; /* Fully pitch black background */
        }

        .starry-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #FFFFFF;
            border-radius: 50%;
            animation: moveStar linear infinite;
            opacity: 0.5;
        }

        @keyframes moveStar {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            5% {
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) translateX(100vw);
                opacity: 0;
            }
        }

        .container {
            background-color: rgba(36, 39, 43, 0.9);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
            z-index: 1;
        }

        h1 {
            color: #00B100;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        p {
            margin-bottom: 1.5rem;
            color: #B0B0B0;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        label {
            font-weight: bold;
            color: #B0B0B0;
        }

        textarea {
            padding: 0.75rem;
            border: 1px solid #00B100;
            border-radius: 8px;
            resize: vertical;
            font-size: 1rem;
            color: #FFFFFF;
            background-color: #24272B;
            font-family: 'Lucida Console', Monaco, monospace;
        }

        input[type="submit"] {
            padding: 0.75rem;
            background-color: #00B100;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            font-family: 'Lucida Console', Monaco, monospace;
        }

        input[type="submit"]:hover {
            background-color: #003580;
        }

        a {
            color: #00B100;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="starry-background">
        <?php for ($i = 0; $i < 100; $i++): ?>
            <div class="star" style="top: <?= rand(-50, 100) ?>%; left: <?= rand(-50, 100) ?>%; animation-duration: <?= rand(20, 60) ?>s; opacity: <?= rand(5, 10) / 10; ?>"></div>
        <?php endfor; ?>
    </div>
    <div class="container">
        <h1>DNS records to CSV</h1>
        <p>Currently supports A, AAAA, CNAME, MX, NS, PTR, SRV, and TXT records.</p>
        <form method="post" action="">
            <label for="domain">Domains (one per line):</label>
            <textarea id="domain" name="domain" rows="4" placeholder="Enter domains here..."></textarea>
            <input type="submit" value="Submit">
        </form>
        <br>
        <p>Find the source code on <a href="https://github.com/rat-blue/dns-dump">Github</a></p>
    </div>
</body>
</html>
