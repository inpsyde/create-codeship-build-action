# Create Codeship Build Action

A GitHub action that creates a build on Codeship via its API.

## Why

Codeship allows connecting a VCS repository so that a build is triggered every time something is 
pushed to that repository.

It might be desirable to trigger a build when changes happen in *another* repository, e. g. one that
is used as a dependency in the repository connected to Codeship.

This action uses the ["Create Build" endpoint in Codeship API](https://apidocs.codeship.com/v2/builds/create-build)
to trigger a Codeship build.

## Usage Example

```yaml
on: [push]
jobs:
    build:
    ​    runs-on: ubuntu-latest
    ​    if: "contains(github.event.head_commit.message, 'codeship')"
    ​    name: Build on Codeship
    ​    steps:
    ​      - name: Checkout
            uses: actions/checkout@v2
    ​      - name: Call Codeship API
            uses: inpsyde/create-codeship-build-action@1.0.0
            with:
                codeship-user: 'jane.doe@example.com'
                codeship-pwd: ${{ secrets.CODESHIP_USER_PASS }}
                codeship-orga: 'my-organization'
                codeship-project: 'b419e642-c6e2-11ea-87d0-0242ac130003'
                codeship-project-ref: 'heads/master'
```

Having a workflow like this in a GitHub repo, will trigger a build on Codeship for every push having
the word "codeship" anywhere in the commit message.

## Inputs

### `codeship-user`

Codeship user's email. **Required**.

### `codeship-pwd`

Codeship user's password. **Required**.
Please use [secrets](https://docs.github.com/en/actions/configuring-and-managing-workflows/creating-and-storing-encrypted-secrets) 
in configuration and do not type visibly.

Please note that Codeship API requires a password.
Users that access via GitHub/GitLab/Bitbucket, should first obtain a password via the "password recovery"
functionality (https://app.codeship.com/password_reset/new).

### `codeship-orga`

Codeship organization name. **Required**.
Note that this is not the "label" but the "slug", which is also used as part of the organization URL.
The URL of a repository whose label is "My Awesome Organization" will be something like
`https://app.codeship.com/my-awesome-organization and my-awesome-organization` is what is need to pass as input.

### `codeship-project`

Codeship project UUID. **Required**. To obtain the UUID it is possible to use Codeship API.
Alternatively, the UUID is used as part of the Codeship "Badge" URL, that can be found in the Codeship
web app, in the "Project Settings" page, in then the "General" tab.

The badge URL will be something like:

`https://app.codeship.com/projects/b419e642-c6e2-11ea-87d0-0242ac130003/status?branch=master`

And in that case the project UUID is `b419e642-c6e2-11ea-87d0-0242ac130003`.

### `codeship-project-ref`

The branch in the repository connected to the Codeship project to trigger the build for.
**Optional**, by default `heads/master`. It must be in the format of GitHub references, 
see https://developer.github.com/v3/git/refs/

Note that is possible to use `${{ github.ref }}` to dynamically set the value to the branch the 
workflow is running (see https://docs.github.com/en/actions/reference/context-and-expression-syntax-for-github-actions#github-context).

## License

Copyright (c) 2020 Inpsyde GmbH

This code is licensed under the [MIT License](LICENSE).

---

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
