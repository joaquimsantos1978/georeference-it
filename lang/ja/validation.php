<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeには:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください。',
    'alpha' => ':attributeはアルファベットのみ使用できます。',
    'alpha_dash' => ':attributeはアルファベットと数字、ダッシュ(-)及び下線(_)が使用できます。',
    'alpha_num' => ':attributeはアルファベットと数字が使用できます。',
    'any_of' => ':attributeは無効です。',
    'array' => ':attributeは配列でなければなりません。',
    'ascii' => ':attributeは半角の英数字と記号のみ使用できます。',
    'before' => ':attributeには:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeは:min個から:max個までの間で指定してください。',
        'file' => ':attributeは:minから:maxキロバイトの間で指定してください。',
        'numeric' => ':attributeは:minから:maxの間で指定してください。',
        'string' => ':attributeは:min文字から:max文字の間で指定してください。',
    ],
    'boolean' => ':attributeはtrueかfalseを指定してください。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeが確認用の入力と一致しません。',
    'contains' => ':attributeに必須の値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeには有効な日付を指定してください。',
    'date_equals' => ':attributeには:dateと同じ日付を指定してください。',
    'date_format' => ':attributeは:formatの形式と一致しません。',
    'decimal' => ':attributeは小数点以下:decimal桁で指定してください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeは:digits桁で指定してください。',
    'digits_between' => ':attributeは:min桁から:max桁の間で指定してください。',
    'dimensions' => ':attributeの画像サイズが正しくありません。',
    'distinct' => ':attributeには重複した値が含まれています。',
    'doesnt_contain' => ':attributeには次の値を含めないでください: :values。',
    'doesnt_end_with' => ':attributeは次のいずれかで終わらないようにしてください: :values。',
    'doesnt_start_with' => ':attributeは次のいずれかで始まらないようにしてください: :values。',
    'email' => ':attributeには、有効なメールアドレス形式を指定してください。',
    'encoding' => ':attributeは:encodingでエンコードされている必要があります。',
    'ends_with' => ':attributeは次のいずれかで終わる必要があります: :values。',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeは次のいずれかの拡張子である必要があります: :values。',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeに値を指定してください。',
    'gt' => [
        'array' => ':attributeは:value個より多くなければなりません。',
        'file' => ':attributeは:valueキロバイトより大きいサイズでなければなりません。',
        'numeric' => ':attributeは:valueより大きい値でなければなりません。',
        'string' => ':attributeは:value文字より多くなければなりません。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上でなければなりません。',
        'file' => ':attributeは:valueキロバイト以上のサイズでなければなりません。',
        'numeric' => ':attributeは:value以上の値でなければなりません。',
        'string' => ':attributeは:value文字以上でなければなりません。',
    ],
    'hex_color' => ':attributeには有効な16進数カラーコードを指定してください。',
    'image' => ':attributeには画像を指定してください。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しません。',
    'in_array_keys' => ':attributeには次のキーのうち少なくとも1つを含めてください: :values。',
    'integer' => ':attributeは整数で指定してください。',
    'ip' => ':attributeには、有効なIPアドレスを指定してください。',
    'ipv4' => ':attributeには、有効なIPv4アドレスを指定してください。',
    'ipv6' => ':attributeには、有効なIPv6アドレスを指定してください。',
    'json' => ':attributeには有効なJSON文字列を指定してください。',
    'list' => ':attributeはリストでなければなりません。',
    'lowercase' => ':attributeは小文字でなければなりません。',
    'lt' => [
        'array' => ':attributeは:value個より少なくなければなりません。',
        'file' => ':attributeは:valueキロバイトより小さいサイズでなければなりません。',
        'numeric' => ':attributeは:valueより小さい値でなければなりません。',
        'string' => ':attributeは:value文字より少なくなければなりません。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下でなければなりません。',
        'file' => ':attributeは:valueキロバイト以下のサイズでなければなりません。',
        'numeric' => ':attributeは:value以下の値でなければなりません。',
        'string' => ':attributeは:value文字以下でなければなりません。',
    ],
    'mac_address' => ':attributeには有効なMACアドレスを指定してください。',
    'max' => [
        'array' => ':attributeは:max個以下でなければなりません。',
        'file' => ':attributeは:maxキロバイトより大きくすることはできません。',
        'numeric' => ':attributeは:maxより大きい値にすることはできません。',
        'string' => ':attributeは:max文字より大きくすることはできません。',
    ],
    'max_digits' => ':attributeは:max桁より多くすることはできません。',
    'mimes' => ':attributeには:valuesタイプのファイルを指定してください。',
    'mimetypes' => ':attributeには:valuesタイプのファイルを指定してください。',
    'min' => [
        'array' => ':attributeは:min個以上でなければなりません。',
        'file' => ':attributeは:minキロバイト以上でなければなりません。',
        'numeric' => ':attributeは:min以上の値にしてください。',
        'string' => ':attributeは:min文字以上にしてください。',
    ],
    'min_digits' => ':attributeは:min桁以上にしてください。',
    'missing' => ':attributeは存在してはいけません。',
    'missing_if' => ':otherが:valueの場合、:attributeは存在してはいけません。',
    'missing_unless' => ':otherが:valueでない限り、:attributeは存在してはいけません。',
    'missing_with' => ':valuesが存在する場合、:attributeは存在してはいけません。',
    'missing_with_all' => ':valuesが全て存在する場合、:attributeは存在してはいけません。',
    'multiple_of' => ':attributeは:valueの倍数でなければなりません。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeには、数字を指定してください。',
    'password' => [
        'letters' => ':attributeは少なくとも1文字のアルファベットを含める必要があります。',
        'mixed' => ':attributeは少なくとも1つの大文字と1つの小文字を含める必要があります。',
        'numbers' => ':attributeは少なくとも1つの数字を含める必要があります。',
        'symbols' => ':attributeは少なくとも1つの記号を含める必要があります。',
        'uncompromised' => '指定された:attributeは過去の情報漏えいで確認されています。別の:attributeを選択してください。',
    ],
    'present' => ':attributeが存在していません。',
    'present_if' => ':otherが:valueの場合、:attributeが存在している必要があります。',
    'present_unless' => ':otherが:valueでない限り、:attributeが存在している必要があります。',
    'present_with' => ':valuesが存在する場合、:attributeが存在している必要があります。',
    'present_with_all' => ':valuesが全て存在する場合、:attributeが存在している必要があります。',
    'prohibited' => ':attributeは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは禁止されています。',
    'prohibited_if_accepted' => ':otherが承認されている場合、:attributeは禁止されています。',
    'prohibited_if_declined' => ':otherが拒否されている場合、:attributeは禁止されています。',
    'prohibited_unless' => ':otherが:valuesに含まれていない限り、:attributeは禁止されています。',
    'prohibits' => ':attributeが存在する場合、:otherは存在できません。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeには次のキーのエントリを含める必要があります: :values。',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認されている場合、:attributeは必須です。',
    'required_if_declined' => ':otherが拒否されている場合、:attributeは必須です。',
    'required_unless' => ':otherが:valuesに含まれていない限り、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesが全て存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesがどれも存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherは一致する必要があります。',
    'size' => [
        'array' => ':attributeは:size個でなければなりません。',
        'file' => ':attributeのサイズは:sizeキロバイトでなければなりません。',
        'numeric' => ':attributeは:sizeでなければなりません。',
        'string' => ':attributeは:size文字でなければなりません。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まる必要があります: :values。',
    'string' => ':attributeは文字列を指定してください。',
    'timezone' => ':attributeには、有効なタイムゾーンを指定してください。',
    'unique' => ':attributeは既に使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字でなければなりません。',
    'url' => ':attributeには、有効な形式を指定してください。',
    'ulid' => ':attributeには、有効なULIDを指定してください。',
    'uuid' => ':attributeには、有効なUUIDを指定してください。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
