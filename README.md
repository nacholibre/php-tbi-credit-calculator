# TBI Credit Calculator
Кредитен калкулатор за TBI Bank написан на PHP

Как се използва?
-----
```
$tbi = new TBICreditCalculator();

// 0 - bez
// 1 - zastrahovka jivot
// 2 - imushtestvo
// 3 - jivot i imushtestvo
// 4 - jivot i bezrabotica
// 5 - jivot, bezrabotica i imushtestvo
$zastrahovka = 0;

$meseciNaKredita = 12;

$cenaZaKredit = 1099;
$purvaVnoska = 0;

$tbiData = $tbi->calc($cenaZaKredit, $purvaVnoska, $meseciNaKredita, $zastrahovka);
```

output
```
Array
(
    [total] => 1296.82
    [monthlyPayment] => 108.07
    [oskupqvane] => 0.18
    [taksaOcenkaNaRiska] => 131.88
    [razmerCredit] => 1230.88
    [glp] => 0.097457885742187
    [gpr] => 0.35635966220093
)
```


Ако искате да промените оскъпяването на месец, такса оценка на риска или застрахователна премия може да разгледате конструктора на класа.
