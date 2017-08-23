#!/usr/bin/env bats

#
# remote-drupal.bats
#
# Run remote newrelic data commands
#

@test "get a sample newrelic data metrics" {
  run terminus newrelic-data:site $TERMINUS_SITE.dev
  [ "$status" -eq 0 ]
  [[ "$output" == *"Response Time"* ]]
  [[ "$output" == *"Throughput"* ]]
  [[ "$output" == *"Error Rate"* ]]
  [[ "$output" == *"Apdex"* ]]
  [[ "$output" == *"Health Status"* ]]
}