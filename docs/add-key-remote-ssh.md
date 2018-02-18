# Add key to remove ssh server #

Make sure your remote ssh server is configured to allow password authentication:

```sh
nano /etc/ssh/sshd_config 
# Look for and make sure  'PasswordAuthentication yes'
```

```sh
service ssh restart
```

Now locally, run:

```sh
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"
```

Now send your generated key to te remote server:

```sh
cat ~/.ssh/id_rsa.pub | ssh root@174.138.6.213 'cat - >> ~/.ssh/authorized_keys'
```

