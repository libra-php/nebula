<?php

namespace Nebula\Framework\Database\Interface;

interface Migration
{
    public function up(): string;
    public function down(): string;
}
