<?php
// Masks offensive review text; set $badText and read $badFound, $badMaskedText, and $badMessage.
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
    'piste', 'bayot', 'buang', 'bobo', 'tanga', 'ulol', 'inutil', 'siraulo',
    'ass', 'arse', 'bastard', 'bugger', 'cock', 'crap', 'damn', 'dick', 'feck', 'piss', 'poof', 'poofter', 'prick', 'pussy', 'jackass',
    'paki', 'spic', 'kike', 'hori',
];
$badAllowed = ['shiitake', 'scunthorpe'];

$badFound = [];
$badRanges = [];
$badClean = strtr(strtolower($badText), ['@' => 'a', '4' => 'a', '0' => 'o', '1' => 'i', '3' => 'e', '$' => 's', '5' => 's', '7' => 't', '!' => 'i', '+' => 't']);
foreach ($badAllowed as $badOk) {                   // Preserve allowed words during scanning.
    $badClean = preg_replace_callback('/' . $badOk . '/', fn($m) => str_repeat('_', strlen($m[0])), $badClean);
}
foreach (['anywhere' => $badAnywhere, 'whole' => $badWholeWord] as $badKind => $badList) {
    foreach ($badList as $badWord) {
        $badPattern = '';
        $letters = preg_replace('/[^a-z0-9]/', '', strtolower($badWord));
        foreach (str_split($letters) as $badLetter) {
            if ($badPattern !== '') {
                $badPattern .= '[^a-z0-9]{0,2}';
            }
            $badPattern .= preg_quote($badLetter, '/') . '+';
        }
        if ($badKind === 'whole') {
            $badPattern = '(?<![a-z0-9])' . $badPattern . '(?![a-z0-9])';
        }
        if (preg_match_all('/' . $badPattern . '/', $badClean, $badHits, PREG_OFFSET_CAPTURE)) {
            foreach ($badHits[0] as $badHit) {
                $badFound[$badHit[1]] = substr($badText, $badHit[1], strlen($badHit[0]));
                $badRanges[] = [$badHit[1], strlen($badHit[0])];
            }
        }
    }
}
$badFound = array_values($badFound);

$badIntervals = [];
foreach ($badRanges as $badRange) {
    $badIntervals[] = [$badRange[0], $badRange[0] + $badRange[1]];
}
usort($badIntervals, fn($a, $b) => $a[0] <=> $b[0]);
$badMerged = [];
foreach ($badIntervals as [$badStart, $badEnd]) {
    $last = count($badMerged) - 1;
    if ($last >= 0 && $badStart <= $badMerged[$last][1]) {
        $badMerged[$last][1] = max($badMerged[$last][1], $badEnd);
    } else {
        $badMerged[] = [$badStart, $badEnd];
    }
}
$badMaskedText = $badText;
foreach (array_reverse($badMerged) as [$badStart, $badEnd]) {
    $badSegment = substr($badClean, $badStart, $badEnd - $badStart);
    $badMaskLength = preg_match_all('/[a-z0-9]/i', $badSegment);
    $badMaskedText = substr_replace($badMaskedText, str_repeat('*', $badMaskLength), $badStart, $badEnd - $badStart);
}

$badMessage = '';
if ($badFound) {
    $badMessage = 'Your review was published, but some inappropriate language was masked with asterisks. '
        . 'Please keep reviews respectful and appropriate.';
}
