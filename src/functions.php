<?php

declare(strict_types=1);

if (!function_exists('app')) {
    /**
     * Return the Leaf instance
     *
     * @return Leaf\App
     */
    function app()
    {
        $app = Leaf\Config::get('app')['instance'] ?? null;

        if (!$app) {
            $app = new Leaf\App();
            Leaf\Config::set('app', ['instance' => $app]);
        }

        return $app;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     *
     * @param mixed $data
     * @return void
     */
    function dd($data, ...$moreData)
    {
        echo '<pre>';
        var_dump($data, ...$moreData);
        echo '</pre>';
        die();
    }
}

if (!function_exists('process_text')) {
    /**
     * Return the Leaf instance
     *
     * @return array
     */
    function process_text(string $ocr_output): array
    {
        // "JMWll YA KENYA g A ,. REPUBLIC OF KINYA\nW... mm. 228788719 & WW. 24798402\n\nluwt\n\nZEDEKIAH HAINA AND‘ElNQﬁ\n19 17 1985\n\nHAL-E!“\n\nBUTEREINJHIAS\n\n“All {an\n\nKWISERO\n\n911qu\n\n20. 02. 2013\n\nMilan-n\n\nw"
        // we search for the following in the text: "REPUBLIC" "KENYA",
        // Extract Name
        // Define the keywords to ignore
        $ignore_keywords = array('JAMHURI YA KENYA', 'REPUBLIC OF KENYA','SERIAL NUMBER','ID NUMBER','PLACE OF ISSUE','BIRTH DATE','ISSUE DATE','FULL NAMES');
        $name = '';
        $id_number = '';
        $place_of_issue = '';
        $serial_number = '';
        $birth_date = '';
        $issue_date = '';

        // Check if OCR output contains ignored keywords
        //split the string into an array of words. split on any whitespace or \n, and remove empty values
        $words = preg_split('/[\s\n]+/', $ocr_output, -1, PREG_SPLIT_NO_EMPTY);
        //check if any of the words in the array partially match any of the ignored keywords
        foreach ($words as $word) {
            foreach ($ignore_keywords as $keyword) {
                // lets search for the word in the keyword(Partial match). ID and ID NUM should match ID NUMBER. SERI and SERI NUM should match SERIAL NUMBER etc
                if (stristr($keyword, $word)) {
                    //if word has "name" in it, then the next word is the name
                    if (stristr($word, 'NAME')) {
                        // the entry after the word "name" is the name, up to where we get date or dat or ate
                        $index_of_date = array_search(strtolower('DATE'), array_map('strtolower', $words));
                        if ($index_of_date === false) {
                            $index_of_date = array_search(strtolower('DAT'), array_map('strtolower', $words));
                        }
                        if ($index_of_date === false) {
                            $index_of_date = array_search(strtolower('ATE'), array_map('strtolower', $words));
                        }
                        $current_index = array_search(strtolower($word), array_map('strtolower', $words));
                        for ($i = $current_index + 1; $i < $index_of_date; $i++) {
                            $name .= $words[$i] . ' ';
                        }
                    }
                    //remove the word from the array
                    $words = array_diff($words, array($word));
                }
            }
        }
        $ocr_output = implode(' ', $words);

        // Extract Serial. its more than 8 digits
        if (preg_match('/[0-9]{8,}/', $ocr_output, $matches)) {
            $serial_number = $matches[0];
            //remove the serial number from the string
            $ocr_output = str_ireplace($serial_number, '', $ocr_output);
        }
        // Extract ID number. its 8 digits
        if (preg_match('/[0-9]{8}/', $ocr_output, $matches)) {
            $id_number = $matches[0];
            //remove the id number from the string
            $ocr_output = str_ireplace($id_number, '', $ocr_output);
        }

        // Extract Birth date
        if (preg_match('/[0-9]{1,2} [0-9]{1,2} [0-9]{4}/i', $ocr_output, $matches)) {
            $birth_date = $matches[0];
        }

        // Extract Place of issue
        if (preg_match('/(?<=HAL-E!“\n\n)[A-Z]+(?: [A-Z]+)+/', $ocr_output, $matches)) {
            $place_of_issue = trim($matches[0]);
        }

        // Extract Issue date
        if (preg_match('/[0-9]{1,2}\. [0-9]{1,2}\. [0-9]{4}/', $ocr_output, $matches)) {
            $issue_date = $matches[0];
        }

        // Put the extracted information into an array
        return array(
            'Name' => $name,
            'ID number' => $id_number,
            'Serial number' => $serial_number,
            'Issue date' => $issue_date,
            'Birth date' => $birth_date,
            'Place of issue' => $place_of_issue
        );
    }
}
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return __DIR__ . "\\..\\public\\$path";
    }
}
if (!function_exists('_env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function _env($key, $default = null)
    {
        $env = array_merge(getenv() ?? [], $_ENV ?? []);

        if (!isset($env[$key]) || (isset($env[$key]) && $env[$key] === null)) {
            $env[$key] = $default;
        }

        return $env[$key] ?? $default;
    }
}
