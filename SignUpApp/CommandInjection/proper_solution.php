<?php
include '../../vendor/autoload.php';

use Zend\Ldap\Attribute;
use Zend\Ldap\Ldap;

/***
 * NOTE: Don't ignore exceptions as seen in this example. Handle and log them properly
 */

// <editor-fold desc="Initiate zend-ldap">
/**
 * LDAP options to pass to zend-ldap
 * Here we're using an account with limited permissions specifically created for this task
 */
$ldap_options = [
    'host'                   => 'my-dc.ad.my-domain',
    'username'               => 'limited-account-name',
    'password'               => 'limited-account-pass',
    'accountDomainName'      => 'ad.my-domain',
    'accountDomainNameShort' => 'my-domain',
    'baseDn'                 => 'OU=Users,DC=ad,DC=my-domain',
];

// Create a new Ldap object with our options
$ldap = new Ldap($ldap_options);

// </editor-fold>

// <editor-fold desc="User creation">

// Mock user input. This would usually come from $_GET or $_POST
$username = '*)(sAMAccountName=*))(|(sAMAccountName=*'; // This is an LDAP injection attempt
$password = 'password';
$address  = 'address';
$phone    = 'phone';

/**
 * Using this method avoids command injections vulnerability
 *
 * @param $ldap Zend\Ldap\Ldap
 * @param $username
 * @param $address
 * @param $phone
 * @param $password
 *
 * @throws \Zend\Ldap\Exception\LdapException
 */
function create_ldap_user($ldap, $username, $address, $phone, $password): void {
    /**
     * Try to bind to the LDAP server. Binding is basically authentication.
     * You can verify a user's credentials this way, if the bind fails the credentials were invalid.
     */
    $ldap->bind();
    
    $entry = [];
    
    // Use zend-ldap's functions to construct the new entry
    // These functions explicitly convert the values into an LDAP compatible string
    Attribute::setAttribute($entry, 'sAMAccountName', $username);
    Attribute::setAttribute($entry, 'objectClass', 'user');
    Attribute::setAttribute($entry, 'address', $address);
    Attribute::setAttribute($entry, 'telephoneNumber', $phone);
    
    // If you leave out the 3rd attribute this defaults to MD5
    // Verify the encoding that you need to use
    Attribute::setPassword($entry, $password);
    
    // Try to add the new entry to LDAP
    // Ldap->add() will throw an exception if the DN is malformed
    $ldap->add('cn=' . $username . ',ou=Users,dc=ad,dc=my-domain', $entry);
}

create_ldap_user($ldap, $username, $address, $phone, $password);
// </editor-fold>


// <editor-fold desc="LDAP Injection">

/**
 * @param $ldap Zend\Ldap\Ldap
 * @param $username
 * @param $attributes
 *
 * @return bool|\Zend\Ldap\Collection
 * @throws \Zend\Ldap\Exception\LdapException
 */
function get_user_attributes($ldap, $username, $attributes) {
    /**
     * Create an LDAP filter string to retrieve user attributes from LDAP
     *      EXAMPLE: (&(sAMAccountName=clarkedg))
     */
    $search_string = '(sAMAccountName=' . $username . ')';
    
    // Submit the search string to LDAP and return the results
    $search_result = $ldap->search(
        $search_string,
        'OU=Users,DC=ad,DC=my-domain', Ldap::SEARCH_SCOPE_SUB,
        $attributes
    );
    
    return $search_result;
}


/**
 * Imagine the following scenario:
 *  When a user visits their profile page they are shown attributes
 *  about their account(displayName, lastLogon, mail, etc.)
 *
 *  This list of attributes is sent to the server from the client.
 */

// Instead of only returning true for my account name it will return true for all objects
$username = 'clarkedg)(|(cn=*';

// This is the default set of attributes that the client requests
$user_attributes = [
    // default attributes that the client requests
    'dn', 'mail', 'displayName',
    
    // Additional attributes from the attacker
    'address', 'unicodePwd'
];

$result = get_user_attributes($ldap, $username, $user_attributes);

// Loop through all results
foreach($result as $res) {
    // Loop through all of the attributes that requested
    foreach($user_attributes as $user_attribute) {
        // echo the line 'attribute_name: attribute_value'
        echo $user_attribute . ': ' . $res[$user_attribute][0];
    }
}


/**
 * In this scenario even if the attacker were able to supply additional attributes
 *  it would not pose a security risk because they could only do so for their own account.
 *
 * If the attacker were able to supply arbitrary usernames it would pose a privacy issue but
 *  not necessiarly a security issue.
 *
 * But combining the two the attacker is able to retrieve any information about any LDAP object within the search scope
 */

// </editor-fold>
