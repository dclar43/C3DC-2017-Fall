C3DC 2017 Fall Notes  
=================================================================  
  
  
These notes are based on incomplete information. Most informaton was gleaned through the Red Team Wiki.  
  
  
The following are rough notes that I wrote down as I walked through the Red Team Wiki notes  

  
  
### Pre-competition day steps  
CLEAR BASH HISTORY  
CLEAR MYSQL HISTORY  

Find files in your home directory with 'hist' in the name  
`$ ls ~ | grep hist`

List files in your home directory with the most recently modified entries at the bottom  

`$ ls -lAtr`

If you're like me and rely on your history to remember commands you should back these up on external storage that isn't connected to the competition network.  

There are many ways to truncate a file, here are two example methods.  

By using an output redirect:  
`$ > ~/__FILE__NAME__`  

By using the truncate command:  
`$ truncate -s 0 ~/__FILE__NAME__`  


Things to avoid  
-      
### Mounting a password protected file system by passing the credentials in the command  
This was found in Team 2's history  

    $ mount -t cifs -o username=webuser,password=Team02webuser,guest,uid=root,dir_mode=755,file_mode=755,noperm //192.168.1.33/homes /mnt/homes/  
    $ sudo mount -t cifs //192.168.1.40/homes /mnt/homes -o username=goose,password=R@nd0m..,domain=team2  

While this shouldn't be something that red team could have read, this is still a less than secure method.    


A more secure way would be  
  
Create a credentials file that only root can read  

    $ sudo vim /root/.cifs_creds  
    $ sudo chmod 500 /root/.cifs_creds  

The contents of which would be  

    username=goose  
    password=R@nd0m..
    domain=team2  

Then mount the file system specifying the credentials file  

    sudo mount -t cifs //192.168.1.40/homes /mnt/homes -o credentials=/root/.cifs_creds  
    
The man page for mount-cifs can be found [here](https://www.samba.org/samba/docs/man/manpages-3/mount.cifs.8.html)


## SignUp app  

### Shell command injection:

This GitHub repository contains an [example PHP app](SignUpApp) showing the vulnerability and a method to avoid it.  
  
*Note: For the love of god don't pass anything to `exec()` or equivilent. There are libraries in pretty much every language you'd use that will handle things better.*  
  
### MySQL injection  
PHP's mysql_* API has been deprecated for 7 years and is known to be insecure. The PHP documentation specifically states this and suggests the mysqli_ API or PDO extension.  
Make sure to spend time googling proper security practices for the language/framework that white team hands you.  


### Hard coded credentials  
This was found in Team 1's app  

    $conn  = mysql_connect('192.168.0.10', 'creator', 'J_Magill');

Credentials should be stored in environment variables or through an equally secure method.  
Another option is to store them in a file.      
With Apache/NGINX you can load a .env file when the daemon first starts. This is secure because, depending on configuration, when the daemons first start they are run as the `root` user. During start up the daemon is able to access files that only root can(e.g. `chown root:root cred_file` `chmod 500 cred_file`).  
All subsequent processes, which in PHP's case is every request, are run as a different user, such as `wwwuser`, which cannot access the credentials file.    

### Terminal session logging
  
From the Red Team Wiki   
> Team 3 is logging all user sessions with the `script` UNIX utility to /var/log/shells (world-readable). Root's sessions are also logged, so we can grab all bash history and all files they've opened

From the [script manpage](http://man7.org/linux/man-pages/man1/script.1.html)  

> script makes a typescript of everything displayed on your terminal.
> It is useful for students who need a hardcopy record of an  
> interactive session as proof of an assignment, as the typescript file  
> can be printed out later with lpr(1).


So Team 3 was aggregating all shell histories into a single world readable file. Don't do this.  


## Other

Default credentials: `freenas.team5.isucdc.com root:cdc`
  
From the Red Team Wiki  

> Team 4 DB: db.team4.isucdc.com/phpmyadmin
  
I'm guessing you left PHPMyAdmin accessable from anywhere rather than just the local host. Right after this on the red team wiki they list your team specific user account passwords so I can only assume PHPMyAdmin is how they got those.
