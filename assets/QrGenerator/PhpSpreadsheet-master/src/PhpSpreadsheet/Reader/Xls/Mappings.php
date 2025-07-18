<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

class Mappings
{
    
    const TFUNC_MAPPINGS = [
        2 => ['ISNA', 1],
        3 => ['ISERROR', 1],
        10 => ['NA', 0],
        15 => ['SIN', 1],
        16 => ['COS', 1],
        17 => ['TAN', 1],
        18 => ['ATAN', 1],
        19 => ['PI', 0],
        20 => ['SQRT', 1],
        21 => ['EXP', 1],
        22 => ['LN', 1],
        23 => ['LOG10', 1],
        24 => ['ABS', 1],
        25 => ['INT', 1],
        26 => ['SIGN', 1],
        27 => ['ROUND', 2],
        30 => ['REPT', 2],
        31 => ['MID', 3],
        32 => ['LEN', 1],
        33 => ['VALUE', 1],
        34 => ['TRUE', 0],
        35 => ['FALSE', 0],
        38 => ['NOT', 1],
        39 => ['MOD', 2],
        40 => ['DCOUNT', 3],
        41 => ['DSUM', 3],
        42 => ['DAVERAGE', 3],
        43 => ['DMIN', 3],
        44 => ['DMAX', 3],
        45 => ['DSTDEV', 3],
        48 => ['TEXT', 2],
        61 => ['MIRR', 3],
        63 => ['RAND', 0],
        65 => ['DATE', 3],
        66 => ['TIME', 3],
        67 => ['DAY', 1],
        68 => ['MONTH', 1],
        69 => ['YEAR', 1],
        71 => ['HOUR', 1],
        72 => ['MINUTE', 1],
        73 => ['SECOND', 1],
        74 => ['NOW', 0],
        75 => ['AREAS', 1],
        76 => ['ROWS', 1],
        77 => ['COLUMNS', 1],
        83 => ['TRANSPOSE', 1],
        86 => ['TYPE', 1],
        97 => ['ATAN2', 2],
        98 => ['ASIN', 1],
        99 => ['ACOS', 1],
        105 => ['ISREF', 1],
        111 => ['CHAR', 1],
        112 => ['LOWER', 1],
        113 => ['UPPER', 1],
        114 => ['PROPER', 1],
        117 => ['EXACT', 2],
        118 => ['TRIM', 1],
        119 => ['REPLACE', 4],
        121 => ['CODE', 1],
        126 => ['ISERR', 1],
        127 => ['ISTEXT', 1],
        128 => ['ISNUMBER', 1],
        129 => ['ISBLANK', 1],
        130 => ['T', 1],
        131 => ['N', 1],
        140 => ['DATEVALUE', 1],
        141 => ['TIMEVALUE', 1],
        142 => ['SLN', 3],
        143 => ['SYD', 4],
        162 => ['CLEAN', 1],
        163 => ['MDETERM', 1],
        164 => ['MINVERSE', 1],
        165 => ['MMULT', 2],
        184 => ['FACT', 1],
        189 => ['DPRODUCT', 3],
        190 => ['ISNONTEXT', 1],
        195 => ['DSTDEVP', 3],
        196 => ['DVARP', 3],
        198 => ['ISLOGICAL', 1],
        199 => ['DCOUNTA', 3],
        207 => ['REPLACEB', 4],
        210 => ['MIDB', 3],
        211 => ['LENB', 1],
        212 => ['ROUNDUP', 2],
        213 => ['ROUNDDOWN', 2],
        214 => ['ASC', 1],
        215 => ['DBCS', 1],
        221 => ['TODAY', 0],
        229 => ['SINH', 1],
        230 => ['COSH', 1],
        231 => ['TANH', 1],
        232 => ['ASINH', 1],
        233 => ['ACOSH', 1],
        234 => ['ATANH', 1],
        235 => ['DGET', 3],
        244 => ['INFO', 1],
        252 => ['FREQUENCY', 2],
        261 => ['ERROR.TYPE', 1],
        271 => ['GAMMALN', 1],
        273 => ['BINOMDIST', 4],
        274 => ['CHIDIST', 2],
        275 => ['CHIINV', 2],
        276 => ['COMBIN', 2],
        277 => ['CONFIDENCE', 3],
        278 => ['CRITBINOM', 3],
        279 => ['EVEN', 1],
        280 => ['EXPONDIST', 3],
        281 => ['FDIST', 3],
        282 => ['FINV', 3],
        283 => ['FISHER', 1],
        284 => ['FISHERINV', 1],
        285 => ['FLOOR', 2],
        286 => ['GAMMADIST', 4],
        287 => ['GAMMAINV', 3],
        288 => ['CEILING', 2],
        289 => ['HYPGEOMDIST', 4],
        290 => ['LOGNORMDIST', 3],
        291 => ['LOGINV', 3],
        292 => ['NEGBINOMDIST', 3],
        293 => ['NORMDIST', 4],
        294 => ['NORMSDIST', 1],
        295 => ['NORMINV', 3],
        296 => ['NORMSINV', 1],
        297 => ['STANDARDIZE', 3],
        298 => ['ODD', 1],
        299 => ['PERMUT', 2],
        300 => ['POISSON', 3],
        301 => ['TDIST', 3],
        302 => ['WEIBULL', 4],
        303 => ['SUMXMY2', 2],
        304 => ['SUMX2MY2', 2],
        305 => ['SUMX2PY2', 2],
        306 => ['CHITEST', 2],
        307 => ['CORREL', 2],
        308 => ['COVAR', 2],
        309 => ['FORECAST', 3],
        310 => ['FTEST', 2],
        311 => ['INTERCEPT', 2],
        312 => ['PEARSON', 2],
        313 => ['RSQ', 2],
        314 => ['STEYX', 2],
        315 => ['SLOPE', 2],
        316 => ['TTEST', 4],
        325 => ['LARGE', 2],
        326 => ['SMALL', 2],
        327 => ['QUARTILE', 2],
        328 => ['PERCENTILE', 2],
        331 => ['TRIMMEAN', 2],
        332 => ['TINV', 2],
        337 => ['POWER', 2],
        342 => ['RADIANS', 1],
        343 => ['DEGREES', 1],
        346 => ['COUNTIF', 2],
        347 => ['COUNTBLANK', 1],
        350 => ['ISPMT', 4],
        351 => ['DATEDIF', 3],
        352 => ['DATESTRING', 1],
        353 => ['NUMBERSTRING', 2],
        360 => ['PHONETIC', 1],
        368 => ['BAHTTEXT', 1],
    ];

    
    const TFUNCV_MAPPINGS = [
        0 => 'COUNT',
        1 => 'IF',
        4 => 'SUM',
        5 => 'AVERAGE',
        6 => 'MIN',
        7 => 'MAX',
        8 => 'ROW',
        9 => 'COLUMN',
        11 => 'NPV',
        12 => 'STDEV',
        13 => 'DOLLAR',
        14 => 'FIXED',
        28 => 'LOOKUP',
        29 => 'INDEX',
        36 => 'AND',
        37 => 'OR',
        46 => 'VAR',
        49 => 'LINEST',
        50 => 'TREND',
        51 => 'LOGEST',
        52 => 'GROWTH',
        56 => 'PV',
        57 => 'FV',
        58 => 'NPER',
        59 => 'PMT',
        60 => 'RATE',
        62 => 'IRR',
        64 => 'MATCH',
        70 => 'WEEKDAY',
        78 => 'OFFSET',
        82 => 'SEARCH',
        100 => 'CHOOSE',
        101 => 'HLOOKUP',
        102 => 'VLOOKUP',
        109 => 'LOG',
        115 => 'LEFT',
        116 => 'RIGHT',
        120 => 'SUBSTITUTE',
        124 => 'FIND',
        125 => 'CELL',
        144 => 'DDB',
        148 => 'INDIRECT',
        167 => 'IPMT',
        168 => 'PPMT',
        169 => 'COUNTA',
        183 => 'PRODUCT',
        193 => 'STDEVP',
        194 => 'VARP',
        197 => 'TRUNC',
        204 => 'USDOLLAR',
        205 => 'FINDB',
        206 => 'SEARCHB',
        208 => 'LEFTB',
        209 => 'RIGHTB',
        216 => 'RANK',
        219 => 'ADDRESS',
        220 => 'DAYS360',
        222 => 'VDB',
        227 => 'MEDIAN',
        228 => 'SUMPRODUCT',
        247 => 'DB',
        255 => '',
        269 => 'AVEDEV',
        270 => 'BETADIST',
        272 => 'BETAINV',
        317 => 'PROB',
        318 => 'DEVSQ',
        319 => 'GEOMEAN',
        320 => 'HARMEAN',
        321 => 'SUMSQ',
        322 => 'KURT',
        323 => 'SKEW',
        324 => 'ZTEST',
        329 => 'PERCENTRANK',
        330 => 'MODE',
        336 => 'CONCATENATE',
        344 => 'SUBTOTAL',
        345 => 'SUMIF',
        354 => 'ROMAN',
        358 => 'GETPIVOTDATA',
        359 => 'HYPERLINK',
        361 => 'AVERAGEA',
        362 => 'MAXA',
        363 => 'MINA',
        364 => 'STDEVPA',
        365 => 'VARPA',
        366 => 'STDEVA',
        367 => 'VARA',
    ];
}
