<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jazykové řetězce pro validaci
    |--------------------------------------------------------------------------
    |
    | Následující jazykové řetězce obsahují výchozí chybové zprávy používané
    | třídou validátoru. Některá z těchto pravidel mají více verzí, jako
    | jsou pravidla pro velikost. Tyto zprávy si zde můžete libovolně upravit.
    |
    */

    'accepted' => 'Pole :attribute musí být přijato.',
    'accepted_if' => 'Pole :attribute musí být přijato, pokud :other je :value.',
    'active_url' => 'Pole :attribute musí být platná URL adresa.',
    'after' => 'Pole :attribute musí obsahovat datum po :date.',
    'after_or_equal' => 'Pole :attribute musí obsahovat datum po :date nebo stejné jako :date.',
    'alpha' => 'Pole :attribute může obsahovat pouze písmena.',
    'alpha_dash' => 'Pole :attribute může obsahovat pouze písmena, čísla, pomlčky a podtržítka.',
    'alpha_num' => 'Pole :attribute může obsahovat pouze písmena a čísla.',
    'any_of' => 'Pole :attribute je neplatné.',
    'array' => 'Pole :attribute musí být pole.',
    'ascii' => 'Pole :attribute může obsahovat pouze jednobajtové alfanumerické znaky a symboly.',
    'before' => 'Pole :attribute musí obsahovat datum před :date.',
    'before_or_equal' => 'Pole :attribute musí obsahovat datum před :date nebo stejné jako :date.',
    'between' => [
        'array' => 'Pole :attribute musí obsahovat od :min do :max položek.',
        'file' => 'Pole :attribute musí mít velikost od :min do :max kilobajtů.',
        'numeric' => 'Pole :attribute musí mít hodnotu od :min do :max.',
        'string' => 'Pole :attribute musí mít od :min do :max znaků.',
    ],
    'boolean' => 'Pole :attribute musí mít hodnotu true nebo false.',
    'can' => 'Pole :attribute obsahuje neoprávněnou hodnotu.',
    'confirmed' => 'Potvrzení pole :attribute se neshoduje.',
    'contains' => 'Poli :attribute chybí povinná hodnota.',
    'current_password' => 'Heslo je nesprávné.',
    'date' => 'Pole :attribute musí obsahovat platné datum.',
    'date_equals' => 'Pole :attribute musí obsahovat datum shodné s :date.',
    'date_format' => 'Pole :attribute musí odpovídat formátu :format.',
    'decimal' => 'Pole :attribute musí mít :decimal desetinných míst.',
    'declined' => 'Pole :attribute musí být odmítnuto.',
    'declined_if' => 'Pole :attribute musí být odmítnuto, pokud :other je :value.',
    'different' => 'Pole :attribute a :other se musí lišit.',
    'digits' => 'Pole :attribute musí mít :digits číslic.',
    'digits_between' => 'Pole :attribute musí mít od :min do :max číslic.',
    'dimensions' => 'Pole :attribute má neplatné rozměry obrázku.',
    'distinct' => 'Pole :attribute obsahuje duplicitní hodnotu.',
    'doesnt_contain' => 'Pole :attribute nesmí obsahovat žádnou z následujících hodnot: :values.',
    'doesnt_end_with' => 'Pole :attribute nesmí končit žádnou z následujících hodnot: :values.',
    'doesnt_start_with' => 'Pole :attribute nesmí začínat žádnou z následujících hodnot: :values.',
    'email' => 'Pole :attribute musí být platná e-mailová adresa.',
    'encoding' => 'Pole :attribute musí být kódováno v :encoding.',
    'ends_with' => 'Pole :attribute musí končit jednou z následujících hodnot: :values.',
    'enum' => 'Vybraná hodnota pole :attribute je neplatná.',
    'exists' => 'Vybraná hodnota pole :attribute je neplatná.',
    'extensions' => 'Pole :attribute musí mít jednu z následujících přípon: :values.',
    'file' => 'Pole :attribute musí být soubor.',
    'filled' => 'Pole :attribute musí mít hodnotu.',
    'gt' => [
        'array' => 'Pole :attribute musí obsahovat více než :value položek.',
        'file' => 'Pole :attribute musí mít velikost větší než :value kilobajtů.',
        'numeric' => 'Pole :attribute musí mít hodnotu větší než :value.',
        'string' => 'Pole :attribute musí mít více než :value znaků.',
    ],
    'gte' => [
        'array' => 'Pole :attribute musí obsahovat :value položek nebo více.',
        'file' => 'Pole :attribute musí mít velikost větší nebo rovnou :value kilobajtů.',
        'numeric' => 'Pole :attribute musí mít hodnotu větší nebo rovnou :value.',
        'string' => 'Pole :attribute musí mít :value znaků nebo více.',
    ],
    'hex_color' => 'Pole :attribute musí obsahovat platnou hexadecimální barvu.',
    'image' => 'Pole :attribute musí být obrázek.',
    'in' => 'Vybraná hodnota pole :attribute je neplatná.',
    'in_array' => 'Pole :attribute musí existovat v :other.',
    'in_array_keys' => 'Pole :attribute musí obsahovat alespoň jeden z následujících klíčů: :values.',
    'integer' => 'Pole :attribute musí být celé číslo.',
    'ip' => 'Pole :attribute musí být platná IP adresa.',
    'ipv4' => 'Pole :attribute musí být platná IPv4 adresa.',
    'ipv6' => 'Pole :attribute musí být platná IPv6 adresa.',
    'json' => 'Pole :attribute musí obsahovat platný řetězec JSON.',
    'list' => 'Pole :attribute musí být seznam.',
    'lowercase' => 'Pole :attribute musí obsahovat pouze malá písmena.',
    'lt' => [
        'array' => 'Pole :attribute musí obsahovat méně než :value položek.',
        'file' => 'Pole :attribute musí mít velikost menší než :value kilobajtů.',
        'numeric' => 'Pole :attribute musí mít hodnotu menší než :value.',
        'string' => 'Pole :attribute musí mít méně než :value znaků.',
    ],
    'lte' => [
        'array' => 'Pole :attribute nesmí obsahovat více než :value položek.',
        'file' => 'Pole :attribute musí mít velikost menší nebo rovnou :value kilobajtů.',
        'numeric' => 'Pole :attribute musí mít hodnotu menší nebo rovnou :value.',
        'string' => 'Pole :attribute nesmí mít více než :value znaků.',
    ],
    'mac_address' => 'Pole :attribute musí obsahovat platnou MAC adresu.',
    'max' => [
        'array' => 'Pole :attribute nesmí obsahovat více než :max položek.',
        'file' => 'Pole :attribute nesmí být větší než :max kilobajtů.',
        'numeric' => 'Pole :attribute nesmí být větší než :max.',
        'string' => 'Pole :attribute nesmí být delší než :max znaků.',
    ],
    'max_digits' => 'Pole :attribute nesmí mít více než :max číslic.',
    'mimes' => 'Pole :attribute musí být soubor typu: :values.',
    'mimetypes' => 'Pole :attribute musí být soubor typu: :values.',
    'min' => [
        'array' => 'Pole :attribute musí obsahovat alespoň :min položek.',
        'file' => 'Pole :attribute musí mít velikost alespoň :min kilobajtů.',
        'numeric' => 'Pole :attribute musí mít hodnotu alespoň :min.',
        'string' => 'Pole :attribute musí mít alespoň :min znaků.',
    ],
    'min_digits' => 'Pole :attribute musí mít alespoň :min číslic.',
    'missing' => 'Pole :attribute nesmí být přítomno.',
    'missing_if' => 'Pole :attribute nesmí být přítomno, pokud :other je :value.',
    'missing_unless' => 'Pole :attribute nesmí být přítomno, pokud :other není :value.',
    'missing_with' => 'Pole :attribute nesmí být přítomno, pokud je přítomno :values.',
    'missing_with_all' => 'Pole :attribute nesmí být přítomno, pokud jsou přítomny :values.',
    'multiple_of' => 'Pole :attribute musí být násobkem :value.',
    'not_in' => 'Vybraná hodnota pole :attribute je neplatná.',
    'not_regex' => 'Formát pole :attribute je neplatný.',
    'numeric' => 'Pole :attribute musí být číslo.',
    'password' => [
        'letters' => 'Pole :attribute musí obsahovat alespoň jedno písmeno.',
        'mixed' => 'Pole :attribute musí obsahovat alespoň jedno velké a jedno malé písmeno.',
        'numbers' => 'Pole :attribute musí obsahovat alespoň jednu číslici.',
        'symbols' => 'Pole :attribute musí obsahovat alespoň jeden symbol.',
        'uncompromised' => 'Zadané :attribute bylo nalezeno v úniku dat. Zvolte prosím jiné :attribute.',
    ],
    'present' => 'Pole :attribute musí být přítomno.',
    'present_if' => 'Pole :attribute musí být přítomno, pokud :other je :value.',
    'present_unless' => 'Pole :attribute musí být přítomno, pokud :other není :value.',
    'present_with' => 'Pole :attribute musí být přítomno, pokud je přítomno :values.',
    'present_with_all' => 'Pole :attribute musí být přítomno, pokud jsou přítomny :values.',
    'prohibited' => 'Pole :attribute je zakázáno.',
    'prohibited_if' => 'Pole :attribute je zakázáno, pokud :other je :value.',
    'prohibited_if_accepted' => 'Pole :attribute je zakázáno, pokud je :other přijato.',
    'prohibited_if_declined' => 'Pole :attribute je zakázáno, pokud je :other odmítnuto.',
    'prohibited_unless' => 'Pole :attribute je zakázáno, pokud :other není v :values.',
    'prohibits' => 'Pole :attribute znemožňuje přítomnost pole :other.',
    'regex' => 'Formát pole :attribute je neplatný.',
    'required' => 'Pole :attribute je povinné.',
    'required_array_keys' => 'Pole :attribute musí obsahovat záznamy pro: :values.',
    'required_if' => 'Pole :attribute je povinné, pokud :other je :value.',
    'required_if_accepted' => 'Pole :attribute je povinné, pokud je :other přijato.',
    'required_if_declined' => 'Pole :attribute je povinné, pokud je :other odmítnuto.',
    'required_unless' => 'Pole :attribute je povinné, pokud :other není v :values.',
    'required_with' => 'Pole :attribute je povinné, pokud je přítomno :values.',
    'required_with_all' => 'Pole :attribute je povinné, pokud jsou přítomny :values.',
    'required_without' => 'Pole :attribute je povinné, pokud :values není přítomno.',
    'required_without_all' => 'Pole :attribute je povinné, pokud není přítomno žádné z :values.',
    'same' => 'Pole :attribute a :other se musí shodovat.',
    'size' => [
        'array' => 'Pole :attribute musí obsahovat :size položek.',
        'file' => 'Pole :attribute musí mít velikost :size kilobajtů.',
        'numeric' => 'Pole :attribute musí mít hodnotu :size.',
        'string' => 'Pole :attribute musí mít :size znaků.',
    ],
    'starts_with' => 'Pole :attribute musí začínat jednou z následujících hodnot: :values.',
    'string' => 'Pole :attribute musí být řetězec.',
    'timezone' => 'Pole :attribute musí obsahovat platné časové pásmo.',
    'unique' => 'Hodnota pole :attribute je již obsazena.',
    'uploaded' => 'Nahrání pole :attribute se nezdařilo.',
    'uppercase' => 'Pole :attribute musí obsahovat pouze velká písmena.',
    'url' => 'Pole :attribute musí být platná URL adresa.',
    'ulid' => 'Pole :attribute musí obsahovat platný ULID.',
    'uuid' => 'Pole :attribute musí obsahovat platné UUID.',

    /*
    |--------------------------------------------------------------------------
    | Vlastní jazykové řetězce pro validaci
    |--------------------------------------------------------------------------
    |
    | Zde můžete zadat vlastní validační zprávy pro atributy pomocí konvence
    | "attribute.rule" pro pojmenování řádků. To umožňuje rychle zadat
    | konkrétní vlastní jazykový řádek pro dané pravidlo atributu.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vlastní atributy pro validaci
    |--------------------------------------------------------------------------
    |
    | Následující jazykové řetězce se používají k nahrazení zástupného
    | symbolu atributu něčím srozumitelnějším, například "E-mailová adresa"
    | místo "email". To nám jednoduše pomáhá zpřehlednit naši zprávu.
    |
    */

    'attributes' => [],

];
