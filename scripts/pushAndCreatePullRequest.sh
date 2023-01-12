#!/bin/bash
#http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail

# directory where the script was launched
SCRIPT_DIR="$(cd "$(dirname $0)" && pwd)"
# TranslationTool directory
TOOL_DIR="$(cd $SCRIPT_DIR/.. && pwd)"

#set -o allexport
#source "$TOOL_DIR/.env"
#set +o allexport

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

    GIT_REPO="https://oauth2:$GITHUB_TOKEN@github.com/$GIT_REPO_USERNAME/$GIT_REPO_NAME.git"

    echo "Using module $MODULE_NAME, repository $GIT_REPO with branch $BRANCH:"
}

function pushTranslationToGit {
	cd "$TOOL_DIR"
	php bin/console prestashop:translation:push-on-git "$GIT_REPO" "$MODULE_NAME" "$WORKDIR" $BRANCH -v
}

echo "You're about to push the catalog to Git."
echo

# shellcheck disable=SC2068
readVariables $@
pushTranslationToGit

echo "Done!"
