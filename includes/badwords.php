<?php
// Filters offensive review text; set $badText and read $badFound / $badMessage.
$badAnywhere = [
    'putangina', 'tangina', 'taena', 'putang', 'putsa', 'namputsa', 'punyeta', 'pakshet', 'bolshet', 'hinayupak',
    'tarantado', 'walanghiya', 'langhiya', 'kapalngmukha', 'buwisit', 'hesusmaryosep', 'anakngputa', 'anakngputsa',
    'ihodeputa', 'yudeputa', 'pukenginamo', 'utenngama', 'utennibapa', 'putangama',
    'walang-hiya', 'walang hiya', 'kapal ng mukha', 'putang ina', 'anak ng puta', 'anak ng putsa', 'iho de puta', 'puke ng ina mo', 'uten ng ama mo', 'uten ni bapa mo',
    'pisteng', 'yawa',
    'fuck', 'shit', 'cunt', 'bitch', 'bullshit', 'asshole', 'asshat', 'assmunch', 'arsehole', 'dumbass', 'dickhead', 'bollocks',
    'blowjob', 'cocksucker', 'motherfucker', 'cornhole', 'wanker', 'whore', 'slut', 'twat',
    'nigger', 'nigga', 'faggot', 'retard', 'coonass', 'wetback', 'chingchong', 'pajeet',
];
$badWholeWord = [
    'gago', 'gaga', 'gagi', 'ogag', 'ogags', 'hayop', 'letse', 'leche', 'lintik', 'tae', 'shet', 'pakyu', 'puta', 'puke', 'kupal', 'supot',
    'piste', 'bayot', 'buang',
    'ass', 'arse', 'bastard', 'bugger', 'cock', 'crap', 'damn', 'dick', 'feck', 'piss', 'poof', 'poofter', 'prick', 'pussy', 'jackass',
    'paki', 'spic', 'kike', 'hori',
];
$badAllowed = ['shiitake', 'scunthorpe'];

$badFound = [];
$badClean = strtr(strtolower($badText), ['@' => 'a', '4' => 'a', '0' => 'o', '1' => 'i', '3' => 'e', '$' => 's', '5' => 's', '7' => 't', '!' => 'i', '+' => 't']);
foreach ($badAllowed as $badOk) {                   // Preserve allowed words during scanning.
    $badClean = preg_replace_callback('/' . $badOk . '/', fn($m) => str_repeat('_', strlen($m[0])), $badClean);
}
foreach (['anywhere' => $badAnywhere, 'whole' => $badWholeWord] as $badKind => $badList) {
    foreach ($badList as $badWord) {
        $badPattern = '';
        foreach (str_split($badWord) as $badLetter) {
            $badPattern .= $badLetter . '+';
        }
        if ($badKind === 'whole') {
            $badPattern = '(?<![a-z0-9])' . $badPattern . '(?![a-z0-9])';
        }
        if (preg_match_all('/' . $badPattern . '/', $badClean, $badHits, PREG_OFFSET_CAPTURE)) {
            foreach ($badHits[0] as $badHit) {
                $badFound[$badHit[1]] = substr($badText, $badHit[1], strlen($badHit[0]));
            }
        }
    }
}
$badFound = array_values($badFound);

$badMessage = '';
if ($badFound) {
    $badShown = '';
    foreach (array_slice($badFound, 0, 5) as $badW) {
        $badShown .= ($badShown ? ', ' : '') . (strlen($badW) <= 2 ? str_repeat('*', strlen($badW)) : substr($badW, 0, 1) . str_repeat('*', strlen($badW) - 2) . substr($badW, -1));
    }
    $badMessage = 'Your review was not saved because it contains language that is not allowed (' . $badShown . '). '
        . 'WatchStore reviews must stay respectful so other buyers can trust them. Insults, curses and slurs, in Tagalog, Bisaya or English, '
        . 'are blocked, even with symbols or numbers in place of letters. Please reword your comment and submit again.';
}
