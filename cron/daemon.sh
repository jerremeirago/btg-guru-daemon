#!/bin/bash
for i in {1..4}; do
    $(which php) artisan api:afl
    if [ $i -lt 4 ]; then
        sleep 15
    fi
done