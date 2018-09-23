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

    'accepted'             => 'The :attribute must be accepted.',
    'active_url'           => 'The :attribute is not a valid URL.',
    'after'                => 'The :attribute must be a date after :date.',
    'alpha'                => 'The :attribute may only contain letters.',
    'alpha_dash'           => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num'            => 'The :attribute may only contain letters and numbers.',
    'array'                => 'The :attribute must be an array.',
    'before'               => 'The :attribute must be a date before :date.',
    'between'              => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file'    => 'The :attribute must be between :min and :max kilobytes.',
        'string'  => 'The :attribute must be between :min and :max characters.',
        'array'   => 'The :attribute must have between :min and :max items.',
    ],
    'boolean'              => 'The :attribute field must be true or false.',
    'confirmed'            => 'The :attribute confirmation does not match.',
    'date'                 => 'The :attribute is not a valid date.',
    'date_format'          => 'The :attribute does not match the format :format.',
    'different'            => 'The :attribute and :other must be different.',
    'digits'               => 'The :attribute must be :digits digits.',
    'digits_between'       => 'The :attribute must be between :min and :max digits.',
    'email'                => 'The :attribute must be a valid email address.',
    'exists'               => 'The selected :attribute is invalid.',
    'filled'               => 'The :attribute field is required.',
    'image'                => 'The :attribute must be an image.',
    'in'                   => 'The selected :attribute is invalid.',
    'integer'              => 'The :attribute must be an integer.',
    'ip'                   => 'The :attribute must be a valid IP address.',
    'json'                 => 'The :attribute must be a valid JSON string.',
    'max'                  => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file'    => 'The :attribute may not be greater than :max kilobytes.',
        'string'  => 'The :attribute may not be greater than :max characters.',
        'array'   => 'The :attribute may not have more than :max items.',
    ],
    'mimes'                => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => 'The :attribute must be at least :min.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'string'  => 'The :attribute must be at least :min characters.',
        'array'   => 'The :attribute must have at least :min items.',
    ],
    'not_in'               => 'The selected :attribute is invalid.',
    'numeric'              => 'The :attribute must be a number.',
    'regex'                => 'The :attribute format is invalid.',
    'required'             => 'The :attribute field is required.',
    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_with'        => 'The :attribute field is required when :values is present.',
    'required_with_all'    => 'The :attribute field is required when :values is present.',
    'required_without'     => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'                 => 'The :attribute and :other must match.',
    'size'                 => [
        'numeric' => 'The :attribute must be :size.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'string'  => 'The :attribute must be :size characters.',
        'array'   => 'The :attribute must contain :size items.',
    ],
    'string'               => 'The :attribute must be a string.',
    'timezone'             => 'The :attribute must be a valid zone.',
    'unique'               => 'The :attribute has already been taken.',
    'url'                  => 'The :attribute format is invalid.',
    'same_parent'          => 'The :resources in all locale verson must have the same parent',
    'all_required_in_all_locales'       => 'All the fields of the :resources , in all locale versions are mandatory',
    'field_required_in_all_locales'       => 'The field :field is required in all locale versions',
    'field_min_in_all_locales'       => 'The field :field must have at least :min characters in all locale versions',
    'there_are_required_fields'       => 'There are required field(d) not filled in one or more locale verion(s)',
    'unique_name_and_slug_in_all_locales'          => 'The :resources in all locale versions must have unique name and slug',
    'unique_title_and_slug_in_all_locales'          => 'The :resources in all locale versions must have unique title and slug',
    'at_least_one_translation_required' => 'The fields required must be informed in at least one of the available cultures',
    'media_text_in_all_cultures_required' => 'The media texts must be informed in all cultures',
    'field_in'=> 'The field :field must have one of the folowing values: :in',
    'password_required'=> 'The password field is required when the selected status is password protected',
    'only_the_followings_cultures_are_valid' => 'Only the followings cultures are valid :locales',
    'only_the_followings_units_are_valid' => 'Only the followings units are valid :units',

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
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'id' => 'id',
        'password' => 'Password',
        'email' => 'Email',
        'name' => 'Name',
        'title' => 'title',
        'description' => 'description',
        'priority' => 'priority',
        'scheduled_to' => 'Scheduled to',
        'cost' => 'Cost',
        'done' => 'done',
        'project_id' => 'project',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'roles' => 'Roles',
        'users' => 'Users',
        'subject' => 'Subject',
        'message' => 'Message',
        'date' => 'Date',
        'image' => 'Image',
        'categories'=> 'categories',
        'category'=> 'category',
        'pages'=> 'pages',
        'page'=> 'page',
        'section'=> 'section',
        'sections'=> 'sections',
        'locale'=> 'locale',
        'content' => 'content',
        'abstract' => 'abstract',
        'short_desc' => 'short description',
        'featured_image_id' => 'featured image',
        'featured_video_id' => 'featured video',
        'contents'=> 'contents'
    ],
    'types' => [
        'html'=> 'HTML',
        'external_video'=> 'external_video'
    ]

];
