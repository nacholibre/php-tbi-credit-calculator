<?php

define('FINANCIAL_ACCURACY', 1.0e-6);
define('FINANCIAL_MAX_ITERATIONS', 100);

class TBICreditCalculator {
    public function __construct() {
        $taksaOcenkaRiska = Array(); //format: key = period na kredita, value = procent taksa

        for ($i = 3; $i <= 36; $i++) {
            $taksa = 0.00;
            if ($i >= 4 && $i <= 11) {
                $taksa = 0.006;
            } else if($i >= 12 && $i <= 36) {
                $taksa = 0.012;
            }

            $taksaOcenkaRiska[$i] = $taksa;
        }

        $zastrahovatelnaPremiqProcentMesec = Array(
            '0' => Array(0.00, 0.00), //bez zastrahovka (do 65 god, nad 65 god),
            '1' => Array(0.0039, 0.00), //zastrahovka jivot (do 65 god, nad 65 god),
            '2' => Array(0.003978, 0.003978), //imushtestvo (do 65 god, nad 65 god),
            '3' => Array(0.006666, 0.000), //jivot i imushtestvo (do 65 god, nad 65 god),
            '4' => Array(0.0066, 0.000), //jivot i bezrabotica (do 65 god, nad 65 god),
            '5' => Array(0.010578, 0.000), //jivot i bezrabotica i imushtestvo (do 65 god, nad 65 god),
        );

        $this->options = Array(
            'oskupqvaneNaMesec' => 0.015, //1.5%
            'taksaOcenkaNaRiska' => $taksaOcenkaRiska,
            'zastrahovatelnaPremiq' => $zastrahovatelnaPremiqProcentMesec,
        );
    }

	/**
	* DATEDIFF
	* Returns the number of date and time boundaries crossed between two specified dates.
	* @param  string  $datepart  is the parameter that specifies on which part of the date to calculate the difference.
	* @param  integer $startdate is the beginning date (Unix timestamp) for the calculation.
	* @param  integer $enddate   is the ending date (Unix timestamp) for the calculation.
	* @return integer the number between the two dates.
	*/
	function DATEDIFF($datepart, $startdate, $enddate)
	{
		switch (strtolower($datepart)) {
			case 'yy':
			case 'yyyy':
			case 'year':
				$di = getdate($startdate);
				$df = getdate($enddate);
				return $df['year'] - $di['year'];
				break;
			case 'q':
			case 'qq':
			case 'quarter':
				die("Unsupported operation");
				break;
			case 'n':
			case 'mi':
			case 'minute':
				return ceil(($enddate - $startdate) / 60);
				break;
			case 'hh':
			case 'hour':
				return ceil(($enddate - $startdate) / 3600);
				break;
			case 'd':
			case 'dd':
			case 'day':
				return ceil(($enddate - $startdate) / 86400);
				break;
			case 'wk':
			case 'ww':
			case 'week':
				return ceil(($enddate - $startdate) / 604800);
				break;
			case 'm':
			case 'mm':
			case 'month':
				$di = getdate($startdate);
				$df = getdate($enddate);
				return ($df['year'] - $di['year']) * 12 + ($df['mon'] - $di['mon']);
				break;
			default:
				die("Unsupported operation");
		}
	}

	/**
	 * XNPV
	 * Returns the net present value for a schedule of cash flows that
	 * is not necessarily periodic. To calculate the net present value
	 * for a series of cash flows that is periodic, use the NPV function.
	 *
	 *        n   /                values(i)               \
	 * NPV = SUM | ---------------------------------------- |
	 *       i=1 |           ((dates(i) - dates(1)) / 365)  |
	 *            \ (1 + rate)                             /
	 *
	 */
	function XNPV($rate, $values, $dates)
	{
		if ((!is_array($values)) || (!is_array($dates))) return null;
		if (count($values) != count($dates)) return null;

		$xnpv = 0.0;
		for ($i = 0; $i < count($values); $i++)
		{
			$xnpv += $values[$i] / pow(1 + $rate, $this->DATEDIFF('day', $dates[0], $dates[$i]) / 365);
		}
		return (is_finite($xnpv) ? $xnpv: null);
	}

	/**
	 * XIRR
	 * Returns the internal rate of return for a schedule of cash flows
	 * that is not necessarily periodic. To calculate the internal rate
	 * of return for a series of periodic cash flows, use the IRR function.
	 *
	 * Adapted from routine in Numerical Recipes in C, and translated
	 * from the Bernt A Oedegaard algorithm in C
	 *
	 */
	function XIRR($values, $dates, $guess = 0.1)
	{
		if ((!is_array($values)) && (!is_array($dates))) return null;
		if (count($values) != count($dates)) return null;

		// create an initial bracket, with a root somewhere between bot and top
		$x1 = 0.0;
		$x2 = $guess;
		$f1 = $this->XNPV($x1, $values, $dates);
		$f2 = $this->XNPV($x2, $values, $dates);
		for ($i = 0; $i < FINANCIAL_MAX_ITERATIONS; $i++)
		{
			if (($f1 * $f2) < 0.0) break;
			if (abs($f1) < abs($f2)) {
				$f1 = $this->XNPV($x1 += 1.6 * ($x1 - $x2), $values, $dates);
			} else {
				$f2 = $this->XNPV($x2 += 1.6 * ($x2 - $x1), $values, $dates);
			}
		}
		if (($f1 * $f2) > 0.0) return null;

		$f = $this->XNPV($x1, $values, $dates);
		if ($f < 0.0) {
			$rtb = $x1;
			$dx = $x2 - $x1;
		} else {
			$rtb = $x2;
			$dx = $x1 - $x2;
		}

		for ($i = 0;  $i < FINANCIAL_MAX_ITERATIONS; $i++)
		{
			$dx *= 0.5;
			$x_mid = $rtb + $dx;
			$f_mid = $this->XNPV($x_mid, $values, $dates);
			if ($f_mid <= 0.0) $rtb = $x_mid;
			if ((abs($f_mid) < FINANCIAL_ACCURACY) || (abs($dx) < FINANCIAL_ACCURACY)) return $x_mid;
		}

		return null;
	}

	///**
	// * RATE
	// *
	// */
	//function RATE($nper, $pmt, $pv, $fv = 0.0, $type = 0, $guess = 0.1)
	//{
	//	$rate = $guess;
	//	$i  = 0;
	//	$x0 = 0;
	//	$x1 = $rate;

	//	if (abs($rate) < FINANCIAL_ACCURACY) {
	//		$y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
	//	} else {
	//		$f = exp($nper * log(1 + $rate));
	//		$y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
	//	}
	//	$y0 = $pv + $pmt * $nper + $fv;
	//	$y1 = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;

	//	// find root by secant method
	//	while ((abs($y0 - $y1) > FINANCIAL_ACCURACY) && ($i < FINANCIAL_MAX_ITERATIONS))
	//	{
	//		$rate = ($y1 * $x0 - $y0 * $x1) / ($y1 - $y0);
	//		$x0 = $x1;
	//		$x1 = $rate;

	//		if (abs($rate) < FINANCIAL_ACCURACY) {
	//			$y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
	//		} else {
	//			$f = exp($nper * log(1 + $rate));
	//			$y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
	//		}

	//		$y0 = $y1;
	//		$y1 = $y;
	//		$i++;
	//	}
	//	return $rate;
	//}


    function rate($month, $payment, $amount) {
        // make an initial guess
        $error = 0.0000001; $high = 1.00; $low = 0.00;
        $rate = (2.0 * ($month * $payment - $amount)) / ($amount * $month);

        while(true) {
            // check for error margin
            $calc = pow(1 + $rate, $month);
            $calc = ($rate * $calc) / ($calc - 1.0);
            $calc -= $payment / $amount;

            if ($calc > $error) {
                // guess too high, lower the guess
                $high = $rate;
                $rate = ($high + $low) / 2;
            } elseif ($calc < -$error) {
                // guess too low, higher the guess
                $low = $rate;
                $rate = ($high + $low) / 2;
            } else {
                // acceptable guess
                break;
            }
        }

        return $rate;
    }

    public function monthlyPaymentWithoutIns($total, $first, $months) {
        $oskupqvaneNaMesec = $this->options['oskupqvaneNaMesec'];

        $obshtaSuma = ($total)*(1+$oskupqvaneNaMesec*$months);

        $monthlyPayment = $obshtaSuma/$months;

        return $monthlyPayment;
    }

    public function getGPR($total, $first, $months) {
        $today = time();

        $dates = Array();

        $today = strtotime('+5 days', $today);

        $monthlyPayments = Array();
        $dates[] = time();
        $monthlyPayments[] = $total*-1;
        for ($k = 1; $k <= $months; $k++) {
            $monthlyPayments[] = $this->monthlyPaymentWithoutIns($total, $first, $months);
            $dates[] = strtotime('+' . $k . ' month', $today);
        }

        $gpr = $this->XIRR($monthlyPayments, $dates, 0.01);

        return $gpr;
    }

    public function calc($total, $first = 0, $months, $zastrahovka = 0) {
        $oskupqvaneNaMesec = $this->options['oskupqvaneNaMesec'];
        $zastrahovkaProcent = $this->options['zastrahovatelnaPremiq'][$zastrahovka][0];

        $obshtaSuma = ($total-$first+$zastrahovkaProcent*$months*$total)*(1+$oskupqvaneNaMesec*$months);

        $monthlyPayment = $obshtaSuma/$months;

        $oskupqvane = $obshtaSuma/($total-$first+$zastrahovkaProcent*$months*$total)-1;

        $procentOcenkaRisk = $this->options['taksaOcenkaNaRiska'][$months];

        $taksaOcenkaNaRiska = ($total-$first+$zastrahovkaProcent*$months*$total)*(($procentOcenkaRisk*1000)/100);

        $razmerCredit = $total-$first+$zastrahovkaProcent * $months*$total+$taksaOcenkaNaRiska;

        $glp = $this->rate($months, $monthlyPayment, $razmerCredit)*12;

        $gpr = $this->getGPR($total, $first, $months);

        return Array(
            'obhstaDuljimaSuma' => $obshtaSuma,
            'mesechnaVnoska' => round($monthlyPayment, 2),
            'oskupqvane' => $oskupqvane,
            'taksaOcenkaNaRiska' => round($taksaOcenkaNaRiska, 2),
            'obhstRazmerNaKredita' => $razmerCredit,
            'glp' => round($glp, 4),
            'gpr' => round($gpr, 4),
        );
    }
}
