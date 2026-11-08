<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Checkout form. Rules holding a regular expression are arrays because a
     * pipe inside a rule string would split it.
     *
     * @var array<string, list<string>|string>
     */
    /** Sign up by email and name. Shield reads this in place of its username rules. */
    public array $registration = [
        'name' => [
            'label'  => 'Full name',
            'rules'  => 'required|min_length[2]|max_length[100]',
            'errors' => [
                'required'   => 'Please enter your name.',
                'min_length' => 'Please enter your full name.',
                'max_length' => 'That name is too long. Please keep it under 100 characters.',
            ],
        ],
        'email' => [
            'label'  => 'Email',
            'rules'  => 'required|valid_email|max_length[254]|is_unique[auth_identities.secret]',
            'errors' => [
                'required'    => 'Please enter your email.',
                'valid_email' => 'That email address does not look right.',
                'is_unique'   => 'An account with this email already exists. Please sign in instead.',
            ],
        ],
        'password' => [
            'label'  => 'Password',
            'rules'  => ['required', 'max_byte[72]', 'strong_password[]'],
            'errors' => [
                'required' => 'Please choose a password.',
                'max_byte' => 'That password is too long. Please keep it under 72 characters.',
            ],
        ],
        'password_confirm' => [
            'label'  => 'Password again',
            'rules'  => 'required|matches[password]',
            'errors' => [
                'required' => 'Please enter your password again.',
                'matches'  => 'The two passwords do not match.',
            ],
        ],
    ];

    public array $booking = [
        'customer_name' => 'required|min_length[2]|max_length[100]',
        'phone'         => ['required', 'regex_match[/^[0-9+()\s-]{7,20}$/]'],
        'wedding_date'  => 'required|valid_date[Y-m-d]',
        'ready_by'      => 'permit_empty|valid_date[H:i]',
        'location'      => 'required|min_length[2]|max_length[255]',
        'people'        => 'required|is_natural_no_zero|less_than_equal_to[20]',
        'notes'         => 'permit_empty|max_length[500]',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    public array $booking_errors = [
        'customer_name' => [
            'required'   => 'Please enter your name.',
            'min_length' => 'Please enter your full name.',
            'max_length' => 'That name is too long. Please keep it under 100 characters.',
        ],
        'phone' => [
            'required'    => 'We need a phone number to confirm your booking.',
            'regex_match' => 'Please enter a valid phone number.',
        ],
        'wedding_date' => [
            'required'   => 'Please choose your wedding date.',
            'valid_date' => 'Please choose a valid date.',
        ],
        'ready_by' => [
            'valid_date' => 'Please choose a valid time.',
        ],
        'location' => [
            'required'   => 'Please tell us the venue or place.',
            'min_length' => 'Please tell us the venue or place.',
            'max_length' => 'That is too long. Please shorten it to 255 characters.',
        ],
        'people' => [
            'required'            => 'Please tell us how many people need draping.',
            'is_natural_no_zero'  => 'Please enter at least 1 person.',
            'less_than_equal_to'  => 'For more than 20 people, please message us.',
        ],
        'notes' => [
            'max_length' => 'Notes can be up to 500 characters.',
        ],
    ];

    public array $enquiry = [
        'customer_name' => 'required|min_length[2]|max_length[100]',
        'phone'         => ['required', 'regex_match[/^[0-9+()\s-]{7,20}$/]'],
        'email'         => 'required|valid_email|max_length[120]',
        'event_date'    => 'permit_empty|valid_date[Y-m-d]',
        'details'       => 'permit_empty|max_length[1000]',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    public array $enquiry_errors = [
        'customer_name' => [
            'required'   => 'Please enter your name.',
            'min_length' => 'Please enter your full name.',
            'max_length' => 'That name is too long. Please keep it under 100 characters.',
        ],
        'phone' => [
            'required'    => 'We need a phone number so we can reach you.',
            'regex_match' => 'Please enter a valid phone number.',
        ],
        'email' => [
            'required'    => 'Please enter your email address.',
            'valid_email' => 'That email address does not look right.',
            'max_length'  => 'That email address is too long.',
        ],
        'event_date' => [
            'valid_date' => 'Please choose a valid date.',
        ],
        'details' => [
            'max_length' => 'Please keep this under 1,000 characters.',
        ],
    ];

    public array $checkout = [
        'customer_name' => 'required|min_length[2]|max_length[100]',
        'phone'         => ['required', 'regex_match[/^[0-9+()\s-]{7,20}$/]'],
        'email'         => 'permit_empty|valid_email|max_length[120]',
        'address'       => 'required|min_length[5]|max_length[255]',
        'city'          => 'required|max_length[80]',
        'state'         => 'required|max_length[80]',
        'postcode'      => ['required', 'regex_match[/^[A-Za-z0-9 -]{3,12}$/]'],
        'notes'         => 'permit_empty|max_length[500]',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    public array $checkout_errors = [
        'customer_name' => [
            'required'   => 'Please enter the name we should deliver to.',
            'min_length' => 'Please enter your full name.',
            'max_length' => 'That name is too long. Please keep it under 100 characters.',
        ],
        'phone' => [
            'required'    => 'We need a phone number so the courier can reach you.',
            'regex_match' => 'Please enter a valid phone number.',
        ],
        'email' => [
            'valid_email' => 'That email address does not look right.',
            'max_length'  => 'That email address is too long.',
        ],
        'address' => [
            'required'   => 'Please enter your delivery address.',
            'min_length' => 'Please enter your full delivery address.',
            'max_length' => 'That address is too long. Please shorten it to 255 characters.',
        ],
        'city' => [
            'required'   => 'Please enter your city or town.',
            'max_length' => 'That city name is too long.',
        ],
        'state' => [
            'required'   => 'Please enter your state.',
            'max_length' => 'That state name is too long.',
        ],
        'postcode' => [
            'required'    => 'Please enter your PIN code.',
            'regex_match' => 'Please enter a valid PIN code.',
        ],
        'notes' => [
            'max_length' => 'Delivery notes can be up to 500 characters.',
        ],
    ];
}
