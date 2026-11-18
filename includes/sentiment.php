<?php
/**
 * MysticMind Sentiment Analysis (lexicon + rules)
 *
 * How it works:
 *  1. Text is split into whole words (so "hopeless" no longer matches "hope",
 *     and "unhappy" no longer matches "happy").
 *  2. Each known word or phrase has a WEIGHT from -4 (very negative) to +4.
 *  3. Rules adjust the weight:
 *       - Negation   ("not happy", "hindi masaya")      → flips and softens it
 *       - Intensifier ("very sad", "sobrang saya")      → makes it stronger
 *       - Diminisher ("a bit worried", "medyo pagod")   → makes it weaker
 *       - "but/pero" → the part AFTER "but" counts more than the part before
 *  4. Emojis add to the score too.
 *  5. The total is normalised into a score from -1.0 to +1.0
 *     (same formula as the VADER sentiment analyser) and turned into a label.
 *
 * Supports English, Tagalog, and Taglish.
 */

// ── Lexicon ───────────────────────────────────────────────────────────────────

function sentimentLexicon() {
    static $lex = null;
    if ($lex !== null) return $lex;

    $lex = [
        // ===== ENGLISH: negative =====
        'afraid'=>-2,'scared'=>-2,'fear'=>-2,'fearful'=>-2,'terrified'=>-3,'frightened'=>-2,
        'angry'=>-2,'mad'=>-2,'furious'=>-3,'annoyed'=>-1,'irritated'=>-1,'frustrated'=>-2,'frustrating'=>-2,
        'anxious'=>-2,'anxiety'=>-2,'nervous'=>-1,'worried'=>-2,'worry'=>-2,'worrying'=>-2,'panic'=>-3,'panicking'=>-3,
        'stress'=>-2,'stressed'=>-2,'stressful'=>-2,'overwhelmed'=>-2,'overwhelming'=>-2,'pressure'=>-1,
        'sad'=>-2,'unhappy'=>-2,'upset'=>-2,'miserable'=>-3,'depressed'=>-3,'depression'=>-3,'down'=>-1,
        'cry'=>-2,'crying'=>-2,'cried'=>-2,'tears'=>-2,'heartbroken'=>-3,'heartbreak'=>-3,
        'grief'=>-3,'grieving'=>-3,'mourning'=>-3,'loss'=>-2,
        'hurt'=>-2,'hurting'=>-2,'pain'=>-2,'painful'=>-2,'suffering'=>-3,'broken'=>-2,
        'lonely'=>-2,'alone'=>-1,'lost'=>-2,'empty'=>-2,'numb'=>-2,'isolated'=>-2,'abandoned'=>-3,'rejected'=>-2,
        'hopeless'=>-3,'helpless'=>-3,'worthless'=>-3,'useless'=>-2,'desperate'=>-3,'trapped'=>-2,'stuck'=>-2,
        'confused'=>-1,'uncertain'=>-1,'unsure'=>-1,'doubt'=>-1,'doubts'=>-1,'insecure'=>-2,'jealous'=>-2,
        'tired'=>-1,'exhausted'=>-2,'drained'=>-2,'burnout'=>-3,
        'bad'=>-2,'terrible'=>-3,'awful'=>-3,'horrible'=>-3,'worst'=>-3,'wrong'=>-1,'difficult'=>-1,'hard'=>-1,
        'fail'=>-2,'failed'=>-2,'failing'=>-2,'failure'=>-2,'mistake'=>-1,'regret'=>-2,'guilt'=>-2,'guilty'=>-2,'shame'=>-2,'ashamed'=>-2,
        'hate'=>-3,'crisis'=>-2,'trouble'=>-2,'problem'=>-1,'problems'=>-1,'struggle'=>-2,'struggling'=>-2,
        'betrayed'=>-3,'cheated'=>-3,'toxic'=>-2,'breakup'=>-2,'divorce'=>-2,'sick'=>-1,'ill'=>-1,
        'disappointed'=>-2,'disappointing'=>-2,'bitter'=>-2,'resent'=>-2,'dread'=>-2,

        // ===== ENGLISH: positive =====
        'happy'=>2,'happier'=>2,'glad'=>2,'joy'=>3,'joyful'=>3,'cheerful'=>2,'delighted'=>3,
        'excited'=>2,'exciting'=>2,'thrilled'=>3,'eager'=>2,
        'love'=>3,'loved'=>3,'loving'=>2,'adore'=>3,
        'grateful'=>3,'thankful'=>3,'blessed'=>3,'blessing'=>2,'blessings'=>2,'appreciate'=>2,
        'calm'=>2,'peaceful'=>2,'peace'=>2,'relaxed'=>2,'relieved'=>2,'content'=>2,'safe'=>1,
        'hope'=>2,'hopeful'=>2,'optimistic'=>2,'positive'=>2,'confident'=>2,'brave'=>2,'strong'=>2,'ready'=>1,
        'motivated'=>2,'inspired'=>2,'determined'=>2,'energized'=>2,'proud'=>2,
        'good'=>2,'great'=>3,'amazing'=>3,'wonderful'=>3,'awesome'=>3,'fantastic'=>3,'excellent'=>3,'best'=>3,'better'=>1,'nice'=>1,'fine'=>1,'okay'=>0.5,'ok'=>0.5,
        'success'=>2,'successful'=>2,'achieve'=>2,'achieved'=>2,'win'=>2,'won'=>2,'passed'=>2,
        'progress'=>2,'improve'=>1,'improving'=>2,'grow'=>1,'growth'=>2,'healing'=>2,'healed'=>2,
        'opportunity'=>2,'clear'=>1,'clarity'=>2,'bright'=>2,'fun'=>2,'enjoy'=>2,'enjoying'=>2,'free'=>1,

        // ===== TAGALOG: negative =====
        'malungkot'=>-2,'nalulungkot'=>-2,'lungkot'=>-2,'kalungkutan'=>-2,
        'iyak'=>-2,'umiiyak'=>-2,'naiiyak'=>-2,'umiyak'=>-2,
        'takot'=>-2,'natatakot'=>-2,'kinakabahan'=>-2,'kaba'=>-2,'nag-aalala'=>-2,'nagaalala'=>-2,'alala'=>-1,'balisa'=>-2,
        'galit'=>-2,'nagagalit'=>-2,'inis'=>-1,'naiinis'=>-1,'badtrip'=>-2,'bwisit'=>-2,'buwisit'=>-2,
        'pagod'=>-1,'napapagod'=>-1,'nakakapagod'=>-2,'hirap'=>-1,'nahihirapan'=>-2,'mahirap'=>-1,
        'sakit'=>-2,'masakit'=>-2,'nasaktan'=>-2,'sinaktan'=>-3,
        'nag-iisa'=>-2,'mag-isa'=>-1,'iniwan'=>-3,'iwan'=>-2,
        'problema'=>-1,'na-stress'=>-2,'nastress'=>-2,
        'gulo'=>-1,'magulo'=>-1,'nalilito'=>-1,'litong'=>-1,'lito'=>-1,
        'pangit'=>-2,'panget'=>-2,'malas'=>-2,'bigo'=>-2,'nabigo'=>-2,'talo'=>-2,'natalo'=>-2,
        'sawa'=>-2,'nagsisisi'=>-2,'pagsisisi'=>-2,'hiya'=>-1,'nahihiya'=>-1,
        'walang kwenta'=>-3,'walang kuwenta'=>-3,'walang gana'=>-2,'walang pag-asa'=>-3,

        // ===== TAGALOG: positive =====
        'masaya'=>2,'saya'=>2,'natutuwa'=>2,'tuwa'=>2,'masayang'=>2,
        'mahal'=>2,'minamahal'=>3,'pagmamahal'=>3,
        'salamat'=>2,'nagpapasalamat'=>3,'pinagpala'=>3,
        'kalmado'=>2,'payapa'=>2,'kapayapaan'=>2,'panatag'=>2,
        'pag-asa'=>2,'umaasa'=>1,'na-excite'=>2,
        'maganda'=>2,'ganda'=>2,'mabuti'=>2,'ayos'=>1,'okay lang'=>0.5,
        'swerte'=>2,'suwerte'=>2,'tagumpay'=>3,'nagtagumpay'=>3,'pasado'=>2,'nakapasa'=>2,
        'malakas'=>1,'gumagaan'=>2,'magaan'=>2,
    ];
    return $lex;
}

/** Multi-word phrases, checked before single words. */
function sentimentPhrases() {
    return [
        'giving up'=>-3,'give up'=>-2,'fed up'=>-2,'burned out'=>-3,'burnt out'=>-3,'falling apart'=>-3,
        "can't sleep"=>-2,'cant sleep'=>-2,'no motivation'=>-2,'no hope'=>-3,'not okay'=>-2,'not ok'=>-2,
        'freaking out'=>-2,'broke up'=>-2,'let down'=>-2,'left out'=>-2,
        'looking forward'=>2,'at peace'=>2,'feel better'=>2,'feeling better'=>2,'so proud'=>3,
        'thank god'=>2,'can\'t wait'=>2,'cant wait'=>2,'new beginning'=>2,'fresh start'=>2,
        'hindi ko alam'=>-1,'di ko alam'=>-1,'ayoko na'=>-3,'suko na'=>-3,'sumusuko na'=>-3,
        'pagod na pagod'=>-3,'hindi okay'=>-2,'di okay'=>-2,'hindi ako okay'=>-2,'wala akong gana'=>-2,
        'ang saya'=>3,'sobrang saya'=>3,'gumaan ang loob'=>3,'excited na'=>2,
    ];
}

function sentimentNegators() {
    return [
        'not','no','never','nothing','nobody','none','neither','nor','without','hardly','barely',
        "don't","dont","doesn't","doesnt","didn't","didnt","isn't","isnt","wasn't","wasnt",
        "aren't","arent","weren't","werent","won't","wont","can't","cant","cannot","couldn't","couldnt",
        "shouldn't","shouldnt","wouldn't","wouldnt","haven't","havent","hasn't","hasnt","ain't",
        'hindi','di','wala','walang','ayaw','ayoko','huwag','wag',
    ];
}

function sentimentIntensifiers() {
    return [
        'very'=>1.5,'really'=>1.4,'so'=>1.4,'extremely'=>1.8,'super'=>1.5,'too'=>1.3,'totally'=>1.4,
        'completely'=>1.5,'absolutely'=>1.5,'incredibly'=>1.6,'deeply'=>1.5,'truly'=>1.3,'utterly'=>1.6,'always'=>1.2,
        'sobrang'=>1.6,'sobra'=>1.5,'napaka'=>1.6,'napakasaya'=>1.6,'grabe'=>1.5,'talaga'=>1.3,'lubos'=>1.5,'ang'=>1.2,
        'slightly'=>0.5,'somewhat'=>0.6,'kinda'=>0.6,'kind'=>0.7,'little'=>0.6,'bit'=>0.6,
        'medyo'=>0.6,'konti'=>0.6,'kaunti'=>0.6,'parang'=>0.8,
    ];
}

function sentimentContrastWords() {
    return ['but','however','although','though','yet','pero','kaso','kaya lang','subalit','ngunit'];
}

function sentimentEmojis() {
    return [
        '😊'=>2,'🙂'=>1,'😄'=>2,'😁'=>2,'😃'=>2,'🥰'=>3,'😍'=>3,'❤️'=>2,'❤'=>2,'💖'=>2,'✨'=>1,'🙏'=>1,'💪'=>2,'🎉'=>2,'<3'=>2,':)'=>1,':D'=>2,
        '😢'=>-2,'😭'=>-3,'😞'=>-2,'😔'=>-2,'😟'=>-2,'😩'=>-2,'😫'=>-2,'💔'=>-3,'😡'=>-3,'😠'=>-2,'😰'=>-2,'😨'=>-2,'😥'=>-2,':('=>-1,":'("=>-2,
    ];
}

// ── Tokeniser ─────────────────────────────────────────────────────────────────

/**
 * Lowercase and split into tokens. Punctuation that ends a clause (. , ! ? ;)
 * is kept as a "|" token so negation doesn't leak across clauses.
 */
function sentimentTokenize($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(['’', '‘', '`'], "'", $text);
    $text = preg_replace('/[.,!?;:\n]+/u', ' | ', $text);
    preg_match_all("/\\||[\\p{L}\\p{N}]+(?:['\\-][\\p{L}\\p{N}]+)*/u", $text, $m);
    return $m[0];
}

// ── Main analysis ─────────────────────────────────────────────────────────────

/**
 * Full analysis. Returns:
 *   label    'positive' | 'neutral' | 'negative'
 *   score    float from -1.0 (very negative) to +1.0 (very positive)
 *   raw      un-normalised total
 *   matches  list of words/phrases that affected the score (for debugging / demo)
 */
function analyzeSentimentDetailed($text) {
    $text = (string)$text;
    $lex          = sentimentLexicon();
    $phrases      = sentimentPhrases();
    $negators     = array_flip(sentimentNegators());
    $intensifiers = sentimentIntensifiers();
    $contrast     = array_flip(sentimentContrastWords());

    $tokens  = sentimentTokenize($text);
    $n       = count($tokens);
    $scores  = [];   // [index => weight] so "but" can re-weight earlier parts
    $matches = [];
    $contrastAt = null;

    for ($i = 0; $i < $n; $i++) {
        $tok = $tokens[$i];
        if ($tok === '|') continue;

        if (isset($contrast[$tok])) { $contrastAt = $i; continue; }

        // 1. Try phrases (longest first: 4, 3, 2 words)
        $weight = null; $len = 1; $term = $tok;
        for ($size = 4; $size >= 2; $size--) {
            if ($i + $size > $n) continue;
            $slice = array_slice($tokens, $i, $size);
            if (in_array('|', $slice, true)) continue;
            $cand = implode(' ', $slice);
            if (isset($phrases[$cand]))      { $weight = $phrases[$cand]; $len = $size; $term = $cand; break; }
            if (isset($lex[$cand]))          { $weight = $lex[$cand];     $len = $size; $term = $cand; break; }
        }
        // 2. Single word
        if ($weight === null && isset($lex[$tok])) $weight = $lex[$tok];
        if ($weight === null) continue;

        $isPhrase = $len > 1;

        // 3. Look back up to 3 words (same clause) for intensifiers and negators
        $negated = false;
        $multiplier = 1.0;
        for ($j = $i - 1; $j >= max(0, $i - 3); $j--) {
            $prev = $tokens[$j];
            if ($prev === '|' || isset($contrast[$prev])) break;
            if (isset($intensifiers[$prev]) && $j === $i - 1) $multiplier *= $intensifiers[$prev];
            elseif (isset($intensifiers[$prev]) && $j === $i - 2) $multiplier *= 1 + ($intensifiers[$prev] - 1) * 0.6;
            // A phrase that already contains its own negation ("not okay", "ayoko na") isn't flipped again
            if (!$isPhrase && isset($negators[$prev])) $negated = !$negated;
        }

        $w = $weight * $multiplier;
        // Negation flips and softens: "not happy" is negative but milder than "sad";
        // "not bad" is mildly positive.
        if ($negated) $w = -$w * 0.6;

        $scores[$i] = $w;
        $matches[]  = ($negated ? 'NOT ' : '') . $term . ' (' . round($w, 2) . ')';
        $i += $len - 1;
    }

    // 4. "but" rule: what comes after "but" matters more
    if ($contrastAt !== null) {
        foreach ($scores as $idx => $w) {
            $scores[$idx] = $idx < $contrastAt ? $w * 0.5 : $w * 1.5;
        }
    }

    $raw = array_sum($scores);

    // 5. Emojis / emoticons
    foreach (sentimentEmojis() as $emo => $w) {
        $count = substr_count($text, $emo);
        if ($count) { $raw += $w * $count; $matches[] = "$emo ($w x$count)"; }
    }

    // 6. Exclamation marks amplify whatever direction the text already has (max +3)
    $excl = min(substr_count($text, '!'), 3);
    if ($excl && $raw != 0) $raw += ($raw > 0 ? 0.3 : -0.3) * $excl;

    // 7. Normalise to -1..+1 (VADER formula)
    $score = $raw / sqrt($raw * $raw + 15);

    if ($score >= 0.2)       $label = 'positive';
    elseif ($score <= -0.2)  $label = 'negative';
    else                     $label = 'neutral';

    return [
        'label'   => $label,
        'score'   => round($score, 3),
        'raw'     => round($raw, 2),
        'matches' => $matches,
    ];
}

/** Returns just the label: 'positive', 'neutral' or 'negative' (used everywhere in the app). */
function analyzeSentiment($text) {
    return analyzeSentimentDetailed($text)['label'];
}

// ── Crisis detection ──────────────────────────────────────────────────────────

/**
 * Detects messages that suggest self-harm or suicidal thoughts (English + Tagalog).
 * When true, the app shows a gentle support message with the NCMH crisis hotline.
 */
function detectCrisis($text) {
    $t = mb_strtolower((string)$text, 'UTF-8');
    $t = str_replace(['’', '‘'], "'", $t);
    $patterns = [
        '/\b(kill|hurt|harm)(ing)? myself\b/u',
        '/\bsuicid(e|al)\b/u',
        '/\bend (it all|my life)\b/u',
        '/\b(want|wanna|going) to die\b/u',
        "/\\bdon'?t want to (live|be alive|exist)\\b/u",
        '/\bno reason to live\b/u',
        '/\bbetter off (dead|without me)\b/u',
        '/\bself[- ]?harm\b/u',
        '/\bmagpakamatay\b/u',
        '/\bpatayin (ko )?ang sarili\b/u',
        '/\bayoko na(ng)? mabuhay\b/u',
        '/\bgusto ko na(ng)? mamatay\b/u',
        '/\bsaktan ang sarili\b/u',
        '/\bwala nang (silbi|saysay) ang buhay\b/u',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $t)) return true;
    }
    return false;
}

/** Support message shown when detectCrisis() is true. */
function getCrisisSupport() {
    return [
        'title'   => 'You don\'t have to carry this alone 💜',
        'message' => 'It sounds like you\'re going through something really heavy. The cards can offer reflection, but please reach out to someone who can truly help. The NCMH Crisis Hotline is free, confidential, and open 24/7.',
        'hotlines'=> [
            'NCMH Crisis Hotline: 1553 (landline, toll-free)',
            'Smart/TNT: 0919-057-1553',
            'Globe/TM: 0917-899-8727',
        ],
    ];
}

// ── Display helpers (unchanged) ───────────────────────────────────────────────

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
