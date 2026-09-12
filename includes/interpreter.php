<?php
require_once __DIR__ . '/sentiment.php';

// Rule-based context-aware interpretation engine
function generateInterpretation($cards, $question, $spreadType, $sentiment) {
    $tone = getSentimentTone($sentiment);
    $questionLower = strtolower($question ?? '');

    // Detect question themes
    $themes = detectThemes($questionLower);

    $output = [];

    if ($spreadType === 'single') {
        $card = $cards[0];
        $output['reading'] = interpretSingleCard($card, $question, $tone, $themes);
        $output['summary'] = generateSummary([$card], $tone, $themes);
        $output['reflection_prompts'] = generateReflectionPrompts([$card], $themes);
    } elseif ($spreadType === 'three_card') {
        $output['past']    = interpretPositionCard($cards[0], 'past', $question, $tone, $themes);
        $output['present'] = interpretPositionCard($cards[1], 'present', $question, $tone, $themes);
        $output['future']  = interpretPositionCard($cards[2], 'future', $question, $tone, $themes);
        $output['summary'] = generateSummary($cards, $tone, $themes);
        $output['reflection_prompts'] = generateReflectionPrompts($cards, $themes);
    } elseif ($spreadType === 'decision') {
        $output['option_a'] = interpretDecisionCard($cards[0], 'Option A', $question, $tone, $themes);
        $output['option_b'] = interpretDecisionCard($cards[1], 'Option B', $question, $tone, $themes);
        $output['outcome']  = interpretDecisionCard($cards[2], 'Overall Outcome', $question, $tone, $themes);
        $output['pros_cons'] = generateProsCons($cards, $themes);
        $output['summary']  = generateSummary($cards, $tone, $themes);
        $output['reflection_prompts'] = generateReflectionPrompts($cards, $themes);
    }

    return $output;
}

function detectThemes($text) {
    $themeMap = [
        'career'       => ['job','career','work','profession','business','promotion','boss','office','salary','course','study','school'],
        'relationship' => ['love','relationship','partner','boyfriend','girlfriend','marriage','family','friend','breakup','divorce'],
        'finance'      => ['money','finance','debt','savings','investment','income','budget','financial'],
        'health'       => ['health','sick','illness','wellness','mental','anxiety','stress','body','healing'],
        'purpose'      => ['purpose','meaning','direction','path','goal','future','life','decision','choice'],
        'growth'       => ['grow','improve','change','better','learn','develop','progress','transform'],
    ];
    $detected = [];
    foreach ($themeMap as $theme => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($text, $kw) !== false) {
                $detected[] = $theme;
                break;
            }
        }
    }
    return array_unique($detected);
}

function interpretSingleCard($card, $question, $tone, $themes) {
    $meaning = $card['reversed'] ? $card['reversed_meaning'] : $card['upright_meaning'];
    $orientation = $card['reversed'] ? 'reversed' : 'upright';

    $themeContext = buildThemeContext($themes, $card);
    $tonePrefix   = getTonePrefix($tone);

    $text = "{$tonePrefix} The {$card['name']} appears {$orientation}, bringing the energy of: {$meaning}. ";
    if (!empty($question)) {
        $text .= "In the context of your question — \"{$question}\" — this card invites you to reflect on {$themeContext}. ";
    }
    $text .= getCardNarrative($card, $tone);
    return $text;
}

function interpretPositionCard($card, $position, $question, $tone, $themes) {
    $meaning = $card['reversed'] ? $card['reversed_meaning'] : $card['upright_meaning'];
    $orientation = $card['reversed'] ? 'reversed' : 'upright';

    $positionMeaning = [
        'past'    => 'what has shaped your journey and the foundations you carry',
        'present' => 'the current energies and challenges you are navigating right now',
        'future'  => 'the potential path ahead and what may unfold if you stay on this course',
    ];

    $text = "In the {$position} position, {$card['name']} ({$orientation}) speaks to {$positionMeaning[$position]}. ";
    $text .= "Its energy of \"{$meaning}\" suggests ";
    $text .= getCardNarrative($card, $tone);
    return $text;
}

function interpretDecisionCard($card, $label, $question, $tone, $themes) {
    $meaning = $card['reversed'] ? $card['reversed_meaning'] : $card['upright_meaning'];
    $orientation = $card['reversed'] ? 'reversed' : 'upright';

    $text = "{$label} — {$card['name']} ({$orientation}): {$meaning}. ";
    $text .= getCardNarrative($card, $tone);
    return $text;
}

function generateProsCons($cards, $themes) {
    $pros = [];
    $cons = [];
    $positiveThemes = ['hope','joy','abundance','victory','celebration','fulfillment','harmony','strength','inspiration'];
    $negativeThemes = ['conflict','grief','anxiety','restriction','shadow','disruption','burden','heartbreak'];

    foreach ($cards as $card) {
        if (in_array($card['theme'], $positiveThemes)) {
            $pros[] = "The {$card['name']} suggests potential for growth and positive outcomes in this direction.";
        } elseif (in_array($card['theme'], $negativeThemes)) {
            $cons[] = "The {$card['name']} warns of possible challenges or emotional costs to consider carefully.";
        } else {
            $pros[] = "The {$card['name']} indicates a balanced path with both opportunities and lessons.";
        }
    }

    return [
        'pros' => array_slice($pros, 0, 3),
        'cons' => array_slice($cons, 0, 3),
    ];
}

function generateSummary($cards, $tone, $themes) {
    $cardNames = implode(', ', array_map(fn($c) => $c['name'], $cards));
    $themeStr  = !empty($themes) ? implode(' and ', $themes) : 'your current situation';

    $summaries = [
        'supportive'   => "This reading, drawing {$cardNames}, reflects a period of deep inner work around {$themeStr}. Be gentle with yourself — the cards encourage patience and self-compassion as you navigate this chapter.",
        'motivational' => "The cards {$cardNames} point toward momentum and growth in {$themeStr}. You have the inner resources to move forward — trust your instincts and take inspired action.",
        'balanced'     => "Your reading with {$cardNames} offers a balanced perspective on {$themeStr}. The cards invite thoughtful reflection before action, honoring both your intuition and practical wisdom.",
    ];

    return $summaries[$tone] ?? $summaries['balanced'];
}

function generateReflectionPrompts($cards, $themes) {
    $prompts = [
        "What emotions arise when you look at these cards?",
        "How does this reading connect to what you are experiencing right now?",
        "What is one small step you could take today that aligns with this message?",
        "Is there something you have been avoiding that this reading is pointing toward?",
        "What would it look like to fully trust yourself in this situation?",
    ];

    // Add theme-specific prompts
    if (in_array('career', $themes)) {
        $prompts[] = "What does success truly mean to you beyond external validation?";
        $prompts[] = "Are you pursuing this path out of passion or out of fear?";
    }
    if (in_array('relationship', $themes)) {
        $prompts[] = "How are you showing up for yourself in this relationship?";
        $prompts[] = "What boundaries might you need to set or honor?";
    }
    if (in_array('purpose', $themes)) {
        $prompts[] = "If fear were not a factor, what would you choose?";
        $prompts[] = "What does your inner voice say when the noise quiets down?";
    }

    shuffle($prompts);
    return array_slice($prompts, 0, 4);
}

function buildThemeContext($themes, $card) {
    if (empty($themes)) return "the deeper meaning this card holds for your path";
    $theme = $themes[0];
    $contexts = [
        'career'       => "how your skills, ambitions, and sense of purpose align with your career choices",
        'relationship' => "the emotional dynamics and connections that shape your relationships",
        'finance'      => "your relationship with security, abundance, and material wellbeing",
        'health'       => "your body, mind, and the energy you bring to your healing journey",
        'purpose'      => "your deeper calling and the direction that feels most authentic to you",
        'growth'       => "the transformation you are undergoing and the growth waiting on the other side",
    ];
    return $contexts[$theme] ?? "the deeper meaning this card holds for your path";
}

function getTonePrefix($tone) {
    $prefixes = [
        'supportive'   => "With compassion and care,",
        'motivational' => "With strength and clarity,",
        'balanced'     => "With wisdom and balance,",
    ];
    return $prefixes[$tone] ?? "With wisdom,";
}

function getCardNarrative($card, $tone) {
    $narratives = [
        'new_start'      => ['supportive'=>"a gentle invitation to begin again, even if the first step feels uncertain.", 'motivational'=>"a powerful call to embrace new beginnings with courage and openness.", 'balanced'=>"an opportunity to start fresh with both excitement and mindful intention."],
        'action'         => ['supportive'=>"that your inner resources are available — you have more power than you realize.", 'motivational'=>"that now is the time to act with confidence and channel your full potential.", 'balanced'=>"that focused, intentional action will yield meaningful results."],
        'intuition'      => ['supportive'=>"that your inner wisdom is your greatest guide right now — trust what you feel.", 'motivational'=>"that your intuition is sharp and ready to lead you toward clarity.", 'balanced'=>"that listening inward will reveal answers that logic alone cannot provide."],
        'transformation' => ['supportive'=>"that endings, though painful, create space for something more aligned to emerge.", 'motivational'=>"that transformation is your superpower — embrace the change unfolding.", 'balanced'=>"that this transition, while challenging, is a necessary part of your evolution."],
        'reflection'     => ['supportive'=>"that solitude and quiet reflection will bring the clarity you are seeking.", 'motivational'=>"that stepping back to reflect will sharpen your vision and renew your purpose.", 'balanced'=>"that inner contemplation is as valuable as outward action right now."],
        'hope'           => ['supportive'=>"that even in difficulty, light and renewal are on their way to you.", 'motivational'=>"that hope is not passive — it is the fuel for your next inspired move.", 'balanced'=>"that maintaining faith while taking practical steps will guide you forward."],
        'balance'        => ['supportive'=>"that finding equilibrium between your needs and responsibilities is an act of self-care.", 'motivational'=>"that balance is your foundation — from here, you can achieve anything.", 'balanced'=>"that harmony between opposing forces will bring sustainable progress."],
        'strength'       => ['supportive'=>"that your quiet courage and compassion are your greatest strengths.", 'motivational'=>"that you are stronger than you know — this challenge is proof of your resilience.", 'balanced'=>"that true strength comes from patience and inner conviction, not force."],
    ];

    $theme = $card['theme'] ?? 'balance';
    if (isset($narratives[$theme][$tone])) return $narratives[$theme][$tone];
    if (isset($narratives[$theme]['balanced'])) return $narratives[$theme]['balanced'];
    return "that this moment holds an important message worth sitting with and exploring deeply.";
}
