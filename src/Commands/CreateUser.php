<?php

namespace Laits\Util\Commands;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laits:create-users';

    /**
     * Include a description for the console command
     *
     * @var string
     */
    protected $description = 'Create a new user with email, name and optional password.';

    /**
     * Execute the console command.
     * This command will create a new user. The command will ask for a person's email address, their name and their password. 
     * Email and name are required. Password is optional. It then reports whether the attempt is successfull or not. 
     */
    public function handle()
    {
        // Ask for user's email and name 
        $email = $this->ask("What's the user's email?");
        $name = $this->ask("What's the user's name?");

        // Ask for user's password and generate randomly if not entered 

        $password = $this->secret("What's the user's password? (Leave blank to generate a random password.)");

        if (!$password){
            $password = Str::random(12);
            $this->info("Generated a random password.");
        }

        // Hash Password 
        $hashedPassword = Hash::make($password);

        // Attempts to create the user with email, name and password 

        try{
            $user = User::create([
                'email' => $email,
                'name' => $name,
                'password' => $hashedPassword
            ]);

            if ($user){
                $this->info("User created successfully for this user: " . $user->name);
            }else{
                $this->error('Failed to create a new user. Please check the input data and try again.');
            }
           
        } catch(\Exception $e){
            $this->error('Error registering new user', $e->getMessage());
        };

    }
}
