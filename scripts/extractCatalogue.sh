#!/bin/bash
#http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail

# directory where the script was launched
SCRIPT_DIR="$(cd "$(dirname $0)" && pwd)"
# TranslationTool directory
TOOL_DIR="$(cd $SCRIPT_DIR/.. && pwd)"

set -o allexport
source "$TOOL_DIR/.env"
set +o allexport

source "$TOOL_DIR/module.cfg"

WORKDIR="$TOOL_DIR/catalog"

function readVariables {
    if [ "$MODULE_NAME" = "" ]; then
      echo "Module name is required"
      exit 1
    fi

    if [ "$GITHUB_TOKEN" = "" ]; then
      echo "GIT token is required in env variables"
      exit 2
    fi

    if [ "$GIT_REPO_USERNAME" = "" ]; then
      echo "GIT repository username or organization is required"
      exit 2
    fi

    if [ "$GIT_REPO_NAME" = "" ]; then
      echo "GIT repository name is required"
      exit 2
    fi

    if [ "$BRANCH" = "" ]; then
      BRANCH="master"
    fi

    GIT_REPO="https://$GITHUB_TOKEN@github.com/$GIT_REPO_USERNAME/$GIT_REPO_NAME.git"

    echo "Using module $MODULE_NAME, repository $GIT_REPO with branch $BRANCH:"
}

function initWorkDir {
    if [ ! -d "$WORKDIR" ]; then
        echo "Creating $WORKDIR"
        mkdir -p "$WORKDIR"
    fi
}


function retrieveModuleFiles {
    echo 'Retrieving module files...'

    pushd "$WORKDIR"
    if [ -d "$WORKDIR/$MODULE_NAME" ]; then
        echo 'Module copy found, cleaning up...'
        cd "$MODULE_NAME"
        git fetch
        git reset --hard origin/$BRANCH
        git clean -fd
    else
        git clone --branch $BRANCH "$GIT_REPO"
        cd "$MODULE_NAME"
    fi

    echo 'Running composer...'
    composer install
}

function setupTranslationTool {
    echo 'Setting up TranslationTool...'

    cd "$TOOL_DIR"
    composer install -n
}


function extractTranslations {
    echo 'Preparing catalog...'

    cd "$TOOL_DIR"
    rm -Rf app/dumps/translatables/*
    #This config file must be an absolute path, otherwise generated file will contains absolute path and we don't want this
    configFile=$WORKDIR/$MODULE_NAME/.t9n.yml

    #always do it from scratch
    fromScratchParam="--from-scratch=true"

    if [ ! -f "$configFile" ]; then
      cp "$TOOL_DIR/.t9n.yml" "$configFile"
    fi

    php bin/console prestashop:translation:extract "$MODULE_NAME" "$configFile" $fromScratchParam -vvv
    php bin/console prestashop:translation:export "$MODULE_NAME" "$WORKDIR/$MODULE_NAME"
}

echo "You are about to generate a module translation catalog"
echo "This script will create/empty the following directory:"
echo
echo "    $WORKDIR"
echo

# shellcheck disable=SC2068
readVariables $@
initWorkDir
retrieveModuleFiles
setupTranslationTool
extractTranslations

echo "Done!"
echo "You can now review the changes in $WORKDIR/$MODULE_NAME"
echo "Then use the script pushAndCreatePullRequest.sh to push the changes and create a pull request on GIT"
