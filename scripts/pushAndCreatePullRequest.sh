#!/bin/bash
#http://redsymbol.net/articles/unofficial-bash-strict-mode/
set -euo pipefail

# directory where the script was launched
SCRIPT_DIR="$(cd "$(dirname $0)" && pwd)"
# TranslationTool directory
TOOL_DIR="$(cd $SCRIPT_DIR/.. && pwd)"

source "$SCRIPT_DIR/module.cfg"

WORKDIR="$TOOL_DIR/catalog"

function readVariables {
    if [ "$MODULE_NAME" = "" ]; then
      echo "Module name is required"
      exit 1
    fi

    if [ "$GIT_REPO" = "" ]; then
      echo "GIT repository is required"
      exit 2
    fi

    if [ "$BRANCH" = "" ]; then
      BRANCH="master"
    fi

    echo "Using module $MODULE_NAME, repository $GIT_REPO with branch $BRANCH:"
}

function pushTranslationToGit {
	cd $TOOL_DIR
	php bin/console prestashop:translation:push-on-git $MODULE_NAME $WORKDIR $BRANCH -v
}

echo "You're about to push the catalog to Git."
echo

readVariables $@
pushTranslationToGit

echo "Done!"
