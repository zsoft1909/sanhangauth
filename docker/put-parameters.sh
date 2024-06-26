#!/bin/sh
jq -c '.[]' params.json | while read i; do
    aws ssm put-parameter --cli-input-json $i --overwrite
done