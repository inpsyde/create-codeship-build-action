#!/bin/sh -l

set -e

export CODESHIP_USER=$1
export CODESHIP_PWD=$2
export CODESHIP_ORGA=$3
export CODESHIP_PROJECT=$4
export CODESHIP_REF=$5

php ./action.php
