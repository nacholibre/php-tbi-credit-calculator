<?php

require 'TBICreditCalculator.php';

function equalFloat($a, $b) {
    if (abs(($a-$b)/$b) < 0.00001) {
        return true;
    }
    return false;
}

$tbi = new TBICreditCalculator();

// 0 - bez
// 1 - zastrahovka jivot
// 2 - imushtestvo
// 3 - jivot i imushtestvo
// 4 - jivot i bezrabotica
// 5 - jivot, bezrabotica i imushtestvo
$zastrahovka = 0;

$meseciNaKredita = 12;

$cenaZaKredit = 1083;
$purvaVnoska = 0;

$tbiData = $tbi->calc($cenaZaKredit, $purvaVnoska, $meseciNaKredita, $zastrahovka);

assert (equalFloat($tbiData['obhstaDuljimaSuma'], 1277.94));
assert (equalFloat($tbiData['mesechnaVnoska'], 106.5));
assert (equalFloat($tbiData['oskupqvane'], 0.18));
assert (equalFloat($tbiData['taksaOcenkaNaRiska'], 129.96));
assert (equalFloat($tbiData['obhstRazmerNaKredita'], 1212.96));
assert (equalFloat($tbiData['glp'], 0.0975));
assert (equalFloat($tbiData['gpr'], 0.3553));

$tbiData = $tbi->calc($cenaZaKredit, $purvaVnoska, 6, $zastrahovka);

assert (equalFloat($tbiData['obhstaDuljimaSuma'], 1180.47));
assert (equalFloat($tbiData['mesechnaVnoska'], 196.75));
assert (equalFloat($tbiData['oskupqvane'], 0.09));
assert (equalFloat($tbiData['taksaOcenkaNaRiska'], 64.98));
assert (equalFloat($tbiData['obhstRazmerNaKredita'], 1147.98));
assert (equalFloat($tbiData['glp'], 0.0964));
assert (equalFloat($tbiData['gpr'], 0.3295));

$tbiData = $tbi->calc($cenaZaKredit, $purvaVnoska, 12, 1);

assert (equalFloat($tbiData['obhstaDuljimaSuma'], 1337.75));
assert (equalFloat($tbiData['mesechnaVnoska'], 111.48));
assert (equalFloat($tbiData['oskupqvane'], 0.18));
assert (equalFloat($tbiData['taksaOcenkaNaRiska'], 136.04));
assert (equalFloat($tbiData['obhstRazmerNaKredita'], 1269.73));
assert (equalFloat($tbiData['glp'], 0.0975));
assert (equalFloat($tbiData['gpr'], 0.3553));

$tbiData = $tbi->calc($cenaZaKredit, 100, 6, 2);

assert (equalFloat($tbiData['obhstaDuljimaSuma'], 1099.65));
assert (equalFloat($tbiData['mesechnaVnoska'], 183.27));
assert (equalFloat($tbiData['oskupqvane'], 0.09));
assert (equalFloat($tbiData['taksaOcenkaNaRiska'], 60.53));
assert (equalFloat($tbiData['obhstRazmerNaKredita'], 1069.38));
assert (equalFloat($tbiData['glp'], 0.0964));
assert (equalFloat($tbiData['gpr'], 0.3295));

echo "All tests passed, good job!\n";
