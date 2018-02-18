# How to configure SFTP #

This instructions are intended to be used to create an sftp user that can only send files, but not login via SSH.

## Create a user and a dir ##

```sh
#Create a new user. Replace newUserName for a desired user name
sudo adduser newUserName

#First, create the directories.
sudo mkdir -p /var/sftp/uploads

# Set the owner of /var/sftp to root.
sudo chown root:root /var/sftp

# Give root write permissions to the same directory, and give other users only read and execute rights.
sudo chmod 755 /var/sftp

# Change the ownership on the uploads directory to newUserName.
sudo chown newUserName:newUserName /var/sftp/uploads
```

## Restricting Access to One Directory ##

```sh
# Open the sshd config file
sudo nano /etc/ssh/sshd_config
```

Scroll to the very bottom of the file and append the following configuration snippet:

    Match User newUserName
    ForceCommand internal-sftp
    PasswordAuthentication yes
    ChrootDirectory /var/sftp
    PermitTunnel no
    AllowAgentForwarding no
    AllowTcpForwarding no
    X11Forwarding no

Then save and close the file.

Here's what each of those directives do:

- *Match User* tells the SSH server to apply the following commands only to the user specified. Here, we specify **newUserName**.
- *ForceCommand internal-sftp* forces the SSH server to run the SFTP server upon login, disallowing shell access.
- *PasswordAuthentication yes* allows password authentication for this user.
- *ChrootDirectory /var/sftp/* ensures that the user will not be allowed access to anything beyond the /var/sftp directory. You can learn more about chroot in this chroot tutorial.
- *AllowAgentForwarding no*, *AllowTcpForwarding no* and *X11Forwarding no* disables port forwarding, tunneling and X11 forwarding for this user.