<?php
// Keyword-based sentiment analysis
function analyzeSentiment($text) {
    $text = strtolower($text);

    $negative = [
        'afraid','angry','anxious','bad','broken','confused','crisis','depressed',
        'desperate','difficult','doubt','fear','frustrated','grief','hard','hate',
        'hopeless','hurt','lost','miserable','overwhelmed','pain','panic','sad',
        'scared','stress','stuck','terrible','tired','trouble','uncertain','unhappy',
        'unsure','worried','wrong','fail','failure','alone','lonely','regret','shame'
    ];

    $positive = [
        'amazing','better','blessed','calm','confident','excited','grateful','great',
        'happy','hope','hopeful','improve','inspired','joy','love','motivated',
        'optimistic','peaceful','positive','progress','proud','ready','strong',
        'success','thankful','wonderful','grow','growth','opportunity','clear',
        'forward','achieve','win','good','best','bright','energized'
    ];

    $negScore = 0;
    $posScore = 0;

    foreach ($negative as $word) {
        if (strpos($text, $word) !== false) $negScore++;
    }
    foreach ($positive as $word) {
        if (strpos($text, $word) !== false) $posScore++;
    }

    if ($negScore > $posScore) return 'negative';
    if ($posScore > $negScore) return 'positive';
    return 'neutral';
}

function getSentimentLabel($sentiment) {
    $labels = [
        'positive' => '✨ Positive',
        'neutral'  => '🌙 Neutral',
        'negative' => '🌧 Needs Support',
    ];
    return $labels[$sentiment] ?? '🌙 Neutral';
}

function getSentimentTone($sentiment) {
    $tones = [
        'positive' => 'motivational',
        'neutral'  => 'balanced',
        'negative' => 'supportive',
    ];
    return $tones[$sentiment] ?? 'balanced';
}
