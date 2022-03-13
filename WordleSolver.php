<?php
$wordleSolver = new wordleSolver(5, false);
$wordleSolver->run();

/**
 * @Todo gérer la casse
 */
class WordleSolver
{

    public const GREEN = 0;
    public const ORANGE = 1;
    public const GREY = 2;

    public  $wordLength = null;
    public  $wordList = [];

    public function __construct(int $wordLength, bool $updateWordsList)
    {
        $this->wordLength = $wordLength;

        if (!file_exists(dirname(__FILE__) . "/dico/{$wordLength}letters.txt") || $updateWordsList) {
            if (!$this->scrapWords()) {
                exit;
            }
        }

        $this->wordList = json_decode(file_get_contents(dirname(__FILE__) . "dico/{$wordLength}letters.txt"));
    }

    public function run()
    {
        $result = null;
        while ($result != '00000') {
            if (count($this->wordList) <= 1000) {
                echo implode(',', $this->wordList) . "\n";
            }
            echo "Entrez le mot a tester :\n";
            $word = rtrim(fgets(STDIN), "\r\n");
            echo "Résultat ? \n";
            $result = rtrim(fgets(STDIN), "\r\n");
            $this->update($word, $result);
        }

        echo "Trouvé";
        exit;
    }

    function update($word, $result)
    {
        if ($result != '00000') {
            unset($this->wordList[array_search($word, $this->wordList)]);
        }

        for ($i = 0; $i < $this->wordLength; $i++) {
            $invalidWords = [];
            switch ($result[$i]) {
                case self::GREEN:
                    foreach ($this->wordList as $w) {
                        if ($word[$i] != $w[$i]) {
                            $invalidWords[] = $w;
                        }
                    }
                    break;
                case self::ORANGE:
                    foreach ($this->wordList as $w) {
                        if (strpos($w, $word[$i]) === false) {
                            $invalidWords[] = $w;
                        } elseif ($word[$i] == $w[$i]) {
                            $invalidWords[] = $w;
                        }
                        if (in_array('milan', $invalidWords)) {
                            echo 'orange ' . $i;
                        }
                    }

                    break;
                case self::GREY:
                    foreach ($this->wordList as $w) {
                        if (strpos($w, $word[$i]) !== false) {
                            // Traite le cas d'une lettre en plusieurs exemplaires
                            $special = false;
                            for ($j = 0; $j < $this->wordLength; $j++) {
                                if ($i != $j && $word[$i] == $word[$j] && $result[$j] == self::ORANGE) {
                                    $special = true;
                                }
                            }
                            if (!$special) {
                                $invalidWords[] = $w;
                            }
                        }
                    }
                    break;
            }

            $this->wordList = array_diff($this->wordList, $invalidWords);
        }
    }

    function getMostUsedWords() {
        // @Todo implement function
    }

    /**
     * Scrap words from https://motsavec.fr/ and store them as json
     *
     * @return bool
     */
    public function scrapWords() : bool
    {
        echo "Scrapping running... \n";
        $page = 1;
        $wordList = [];
        $running = true;
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        while ($running) {
            $dom->loadHTMLFile("https://motsavec.fr/$this->wordLength-lettres/$page");
            $xpath = new DomXPath($dom);

            $domWords = $xpath->query("//ul[@class='inline-list words group0 sort']/li/a");

            if ($domWords->length) {
                foreach ($domWords as $word) {
                    if (!preg_match('/[A-Z]|’|-|\.|æ|Æ/|œ', $word->textContent)) {
                        $wordList[] = $this->stripAccents($word->textContent);
                    }
                }
            } else {
                $running = false;
            }

            echo "Pages $page \r";
            $page++;
        }

        if (file_put_contents(dirname(__FILE__) . "dico/{$this->wordLength}letters.txt", json_encode($wordList))) {
            echo "Scrapping finished\n";
            return true;
        } else {
            echo "An error occurred\n";
            return false;
        }
    }

    public function stripAccents($str): string
    {
        return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }
}