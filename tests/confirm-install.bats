#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Composer plugin have been installed correctly
#

@test "confirm terminus version" {
      terminus --version
}

@test "get help on newrelic-data command" {
      run terminus help newrelic-data:org
        [[ $output == *"plan"* ]]
          [ "$status" -eq 0 ]
}
