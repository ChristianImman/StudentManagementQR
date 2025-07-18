<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Composer\Pcre\Preg;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

class FunctionPrefix
{
    const XLFNREGEXP = '/(?:_xlfn\.)?((?:_xlws\.)?\b('
        
        . 'beta[.]dist'
        . '|beta[.]inv'
        . '|binom[.]dist'
        . '|binom[.]inv'
        . '|ceiling[.]precise'
        . '|chisq[.]dist'
        . '|chisq[.]dist[.]rt'
        . '|chisq[.]inv'
        . '|chisq[.]inv[.]rt'
        . '|chisq[.]test'
        . '|confidence[.]norm'
        . '|confidence[.]t'
        . '|covariance[.]p'
        . '|covariance[.]s'
        . '|erf[.]precise'
        . '|erfc[.]precise'
        . '|expon[.]dist'
        . '|f[.]dist'
        . '|f[.]dist[.]rt'
        . '|f[.]inv'
        . '|f[.]inv[.]rt'
        . '|f[.]test'
        . '|floor[.]precise'
        . '|gamma[.]dist'
        . '|gamma[.]inv'
        . '|gammaln[.]precise'
        . '|lognorm[.]dist'
        . '|lognorm[.]inv'
        . '|mode[.]mult'
        . '|mode[.]sngl'
        . '|negbinom[.]dist'
        . '|networkdays[.]intl'
        . '|norm[.]dist'
        . '|norm[.]inv'
        . '|norm[.]s[.]dist'
        . '|norm[.]s[.]inv'
        . '|percentile[.]exc'
        . '|percentile[.]inc'
        . '|percentrank[.]exc'
        . '|percentrank[.]inc'
        . '|poisson[.]dist'
        . '|quartile[.]exc'
        . '|quartile[.]inc'
        . '|rank[.]avg'
        . '|rank[.]eq'
        . '|stdev[.]p'
        . '|stdev[.]s'
        . '|t[.]dist'
        . '|t[.]dist[.]2t'
        . '|t[.]dist[.]rt'
        . '|t[.]inv'
        . '|t[.]inv[.]2t'
        . '|t[.]test'
        . '|var[.]p'
        . '|var[.]s'
        . '|weibull[.]dist'
        . '|z[.]test'
        
        . '|acot'
        . '|acoth'
        . '|arabic'
        . '|averageifs'
        . '|binom[.]dist[.]range'
        . '|bitand'
        . '|bitlshift'
        . '|bitor'
        . '|bitrshift'
        . '|bitxor'
        . '|ceiling[.]math'
        . '|combina'
        . '|cot'
        . '|coth'
        . '|csc'
        . '|csch'
        . '|days'
        . '|dbcs'
        . '|decimal'
        . '|encodeurl'
        . '|filterxml'
        . '|floor[.]math'
        . '|formulatext'
        . '|gamma'
        . '|gauss'
        . '|ifna'
        . '|imcosh'
        . '|imcot'
        . '|imcsc'
        . '|imcsch'
        . '|imsec'
        . '|imsech'
        . '|imsinh'
        . '|imtan'
        . '|isformula'
        . '|iso[.]ceiling'
        . '|isoweeknum'
        . '|munit'
        . '|numbervalue'
        . '|pduration'
        . '|permutationa'
        . '|phi'
        . '|rri'
        . '|sec'
        . '|sech'
        . '|sheet'
        . '|sheets'
        . '|skew[.]p'
        . '|unichar'
        . '|unicode'
        . '|webservice'
        . '|xor'
        
        . '|forecast[.]et2'
        . '|forecast[.]ets[.]confint'
        . '|forecast[.]ets[.]seasonality'
        . '|forecast[.]ets[.]stat'
        . '|forecast[.]linear'
        . '|switch'
        
        . '|concat'
        . '|ifs'
        . '|maxifs'
        . '|minifs'
        . '|textjoin'
        
        . '|anchorarray'
        . '|arraytotext'
        . '|bycol'
        . '|byrow'
        . '|call'
        . '|choosecols'
        . '|chooserows'
        . '|drop'
        . '|expand'
        . '|filter'
        . '|groupby'
        . '|hstack'
        . '|isomitted'
        . '|lambda'
        . '|let'
        . '|makearray'
        . '|map'
        . '|randarray'
        . '|reduce'
        . '|register[.]id'
        . '|scan'
        . '|sequence'
        . '|single'
        . '|sort'
        . '|sortby'
        . '|take'
        . '|textafter'
        . '|textbefore'
        . '|textjoin'
        . '|textsplit'
        . '|tocol'
        . '|torow'
        . '|unique'
        . '|valuetotext'
        . '|vstack'
        . '|wrapcols'
        . '|wraprows'
        . '|xlookup'
        . '|xmatch'
        . '))\s*\(/Umui';

    const XLWSREGEXP = '/(?<!_xlws\.)('
        
        . 'filter'
        . '|sort'
        . ')\s*\(/mui';

    
    protected static function addXlfnPrefix(string $functionString): string
    {
        return Preg::replace(self::XLFNREGEXP, '_xlfn.$1(', $functionString);
    }

    
    protected static function addXlwsPrefix(string $functionString): string
    {
        return Preg::replace(self::XLWSREGEXP, '_xlws.$1(', $functionString);
    }

    
    public static function addFunctionPrefix(string $functionString): string
    {
        $functionString = Preg::replaceCallback(
            Calculation::CALCULATION_REGEXP_CELLREF_SPILL,
            fn (array $matches) => 'ANCHORARRAY(' . substr((string) $matches[0], 0, -1) . ')',
            $functionString
        );

        return self::addXlwsPrefix(self::addXlfnPrefix($functionString));
    }

    
    public static function addFunctionPrefixStripEquals(string $functionString): string
    {
        return self::addFunctionPrefix(substr($functionString, 1));
    }
}
