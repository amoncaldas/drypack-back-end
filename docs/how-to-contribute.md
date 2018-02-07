# This document describes how to contribute to the DryPack #

Al the contributions to the DryPack Framework are very welcomed. To do so you have to clone the original source code running:

```sh
git clone -b develop git@gitlab.com:drypack/back-end-php.git DryPack
```

Then run the contribute configure script. This will configure the project in the contribute mode.

```sh
git clone -b develop git@gitlab.com:drypack/back-end-php.git DryPack
cd DryPack
sh scripts/configure-contribute.sh
```

The contribute mode means that the project will be ready for contribution, so you can make changes in the DryPack source and then send a push request. In this mode, the client and admin front-ends are kept in separated git repository (as it is in the original DryPack repository). So, if you make changes in any front-end code, you have to go to the front-end root folder and commit there. For example, "cd public/admin && git status". the same for the client front-end

## Repositories ##

The source code of the DryPack is splitted in different repositories:

- [DryPack back-end](https://gitlab.com/drypack/back-end-php) (main project)
- [DryPack front-end admin](https://gitlab.com/drypack/front-end-admin)
- [DryPack front-end client](https://gitlab.com/drypack/front-end-client)
- [NgC2 lib](https://gitlab.com/drypack/ngc2)
- [C2YoGenerator](https://gitlab.com/drypack/c2yogenerator)

When you clone the main project and run the contribute configuration, the front-end admin and front-end client are downloaded in the public folder so you can run the whole application, but the front-ends repositories a kept separated.

## How to contribute ##

- 1 - You need to choose to which application (Back-end, Front-end client, Front-end admin) you want to contribute.
- 3 - Make the changes and make a local commit in the target repository (main, client or admin front-end)
- 4 - Make a push request
