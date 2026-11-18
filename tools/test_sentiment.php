<?php
/**
 * Sentiment test / demo — run from the command line:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\mysticmind\tools\test_sentiment.php
 * Or test your own sentence:
 *   C:\xampp\php\php.exe tools\test_sentiment.php "medyo kinakabahan ako sa exam"
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/sentiment.php';

$cases = $argc > 1 ? [[implode(' ', array_slice($argv, 1)), null]] : [
    // [text, expected]
    ['I feel hopeless about my career',                    'negative'],
    ['I am unhappy with my job',                           'negative'],
    ['I am not happy with my relationship',                'negative'],
    ['Things are not bad, actually',                       'positive'],
    ['I am very excited about my new job!',                'positive'],
    ['I am a bit worried about the exam',                  'negative'],
    ['I was scared at first but now I feel hopeful',       'positive'],
    ['It started great but now everything is falling apart','negative'],
    ['Will I get the promotion?',                          'neutral'],
    ['What does my future look like?',                     'neutral'],
    ['Sobrang saya ko ngayon',                             'positive'],
    ['Malungkot ako at pagod na pagod',                    'negative'],
    ['Hindi ako masaya sa trabaho ko',                     'negative'],
    ['Medyo kinakabahan ako sa board exam',                'negative'],
    ['Kinakabahan ako pero excited din',                   'positive'],
    ['Hindi ko alam kung tama ang desisyon ko',            'negative'],
    ['Super stressed na ako sa thesis namin',              'negative'],
    ['Grateful ako sa lahat ng blessings 🙏',               'positive'],
    ['My heart is broken 💔😭',                             'negative'],
    ['I hope things get better',                           'positive'],
];

$pass = 0; $total = 0;
foreach ($cases as [$text, $expected]) {
    $r = analyzeSentimentDetailed($text);
    $ok = $expected === null ? '' : ($r['label'] === $expected ? 'PASS' : 'FAIL');
    if ($expected !== null) { $total++; if ($ok === 'PASS') $pass++; }
    printf("%-4s %-8s %+.3f  %s\n       ↳ %s\n", $ok, $r['label'], $r['score'], $text,
        $r['matches'] ? implode(', ', $r['matches']) : '(no sentiment words)');
    if (detectCrisis($text)) echo "       ⚠ crisis language detected\n";
}
if ($total) echo "\n$pass / $total passed\n";
