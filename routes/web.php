<?php

// Public service routes precede the legacy admin service resource routes.
require __DIR__.'/website.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/portfolio.php';
