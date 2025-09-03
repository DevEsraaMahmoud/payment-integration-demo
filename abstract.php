<?php

// 1. Parent Abstract Class
abstract class Employee {
    protected string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    // abstract method → لازم تتنفذ في أول Concrete Class
    abstract public function calculateSalary(): float;

    // concrete method → ممكن تتستخدم أو تعمل لها override أو تتجاهلها
    public function printName(): void {
        echo "Employee name: {$this->name}\n";
    }
}

// 2. Abstract Child Class (لسه ما نفذش calculateSalary)
abstract class TemporaryEmployee extends Employee {
    public function printName(): void {
        echo "TemporaryEmployee name: {$this->name}\n";
    }
    // مفيش تنفيذ لـ calculateSalary هنا → القانون يتأجل للي بعده
}

// 3. Concrete Child Class
class Intern extends TemporaryEmployee {
    public function calculateSalary(): float {
        // تنفيذ خاص بالـ Intern
        return 3000.0;
    }

    // عمل override للـ printName
    public function printName(): void {
        echo "Intern name: {$this->name}\n";
    }
}

// 4. Concrete Child Class تاني
class Admin extends Employee {
    public function calculateSalary(): float {
        // تنفيذ خاص بالـ Admin
        return 10000.0;
    }
    // هنا مستخدم printName زي ما هي من الـ Parent
}

// 5. Concrete Child Class يتجاهل printName ويكتب طريقة خاصة به
class Contractor extends Employee {
    public function calculateSalary(): float {
        // تنفيذ خاص بالـ Contractor
        return 5000.0;
    }

    public function printContractDetails(): void {
        echo "Contractor {$this->name} works on hourly basis.\n";
    }
}

// ========== Usage ==========

/*
will not work because Employee is abstract
$employee = new Employee("John");
echo $employee->calculateSalary() . "\n";  // 5000
$employee->printName();                    // Employee name: John
*/

// will work because Intern is not abstract
$intern = new Intern("Ali");
echo $intern->calculateSalary() . "\n";   // 3000
$intern->printName();                     // Intern name: Ali

// will work because Admin is not abstract
$admin = new Admin("Sara");
echo $admin->calculateSalary() . "\n";    // 10000
$admin->printName();                      // Employee name: Sara

// will work because Contractor is not abstract
$contractor = new Contractor("Omar");
echo $contractor->calculateSalary() . "\n";  // 5000
$contractor->printContractDetails();         // Contractor Omar works on hourly basis.
