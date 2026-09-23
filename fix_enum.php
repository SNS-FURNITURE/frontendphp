<?php
DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'issued', 'approved', 'paid', 'overdue', 'cancelled') DEFAULT 'draft';");
echo "OK\n";
