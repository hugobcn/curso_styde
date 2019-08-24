<?php

namespace Tests\Feature;

use App\Profession;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Throwable;

class UsersModuleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_shows_the_users_list()
    {
        factory(Profession::class)->times(5)->create();

        factory(User::class)->create([
            'name' => 'Joel',
            'profession_id' => Profession::all()->random()->id
        ]);

        factory(User::class)->create([
            'name'=>'Ellie',
            'profession_id' => Profession::all()->random()->id
        ]);

        $this->get('/usuarios')
            ->assertStatus(200)
            ->assertSee('Listado de usuarios')
            ->assertSee('Joel')
            ->assertSee('Ellie');
    }

    /** @test */
    function it_shows_a_defaut_message_if_the_users_list_is_empty()
    {
        //DB::table('users')->truncate();
        
        $this->get('/usuarios')
            ->assertStatus(200)
            ->assertSee('No hay usuarios registrados');
    }

    /** @test */
    function it_displays_the_users_details()
    {
        $user = factory(User::class)->create([
            'name' => 'Marcelo Gularte',
        ]);

        $this->get('/usuarios/'.$user->id)
            ->assertStatus(200)
            ->assertSee('Marcelo Gularte');
            //->assertSee('Mostrando detalle  del usuario: '.$user->id);
    }

    /** @test */
    function it_display_a_404_error_if_the_user_is_not_found(){
        $this->get('/usuarios/999')
            ->assertStatus(404)
            ->assertSee('Página no encontrada');
    }

    /** @test */
    function  it_loads_the_new_users_page()
    {
        $this->withoutExceptionHandling();

        $this->get('/usuarios/nuevo')
            ->assertStatus(200)
            ->assertSee('Crear usuario');
    }

    /** @test */
    function it_crates_a_new_user(){
        $this->withoutExceptionHandling();
        $this->post('/usuarios', [
            'name'=> 'Esteban Novo',
            'email'=> 'novo.esteban@gmail.com',
            'password' => 'laravel'
        ])->assertRedirect(route('users.index'));

           // ->assertSee('Procesando información...');

        $this->assertDatabaseHas('users', [
            'name'=> 'Esteban Novo',
            'email'=> 'novo.esteban@gmail.com'
        ]);

        $this->assertCredentials([
            'name'=> 'Esteban Novo',
            'email'=> 'novo.esteban@gmail.com',
            'password' => 'laravel'
        ]);
    }

    /** @test */
    function the_name_is_required(){
        //$this->withoutExceptionHandling();

        $this
            ->from('usuarios/nuevo')
            ->post('/usuarios', [
                'name'=> '',
                'email'=> 'novo.esteban@gmail.com',
                'password' => 'laravel'
            ])
            ->assertRedirect('usuarios/nuevo')
            ->assertSessionHasErrors(['name' => 'The field name is required']);

        /*$this->assertDatabaseMissing('users',[
            'email'=> 'novo.esteban@gmail.com'
        ]);*/

        $this->assertEquals(0, User::count());
    }

    /** @test */
    function the_email_is_required(){
        //$this->withoutExceptionHandling();

        $this
            ->from('usuarios/nuevo')
            ->post('/usuarios', [
                'name'=> 'Esteban Novo',
                'email'=> '',
                'password' => 'laravel'
            ])
            ->assertRedirect('usuarios/nuevo')
            ->assertSessionHasErrors(['email' => 'The field email is required']);

        $this->assertEquals(0, User::count());
    }


    /** @test */
    function the_password_is_required(){
        //$this->withoutExceptionHandling();

        $this
            ->from('usuarios/nuevo')
            ->post('/usuarios', [
                'name'=> 'Esteban Novo',
                'email'=> 'novo.esteban@gmail.com',
                'password' => ''
            ])
            ->assertRedirect('usuarios/nuevo')
            ->assertSessionHasErrors(['password' => 'The field password is required']);

        $this->assertEquals(0, User::count());
    }

    /** @test */
    function the_email_must_be_valid(){
        //$this->withoutExceptionHandling();

        $this
            ->from('usuarios/nuevo')
            ->post('/usuarios', [
                'name'=> 'Esteban Novo',
                'email'=> 'correo-invalido-dev',
                'password' => ''
            ])
            ->assertRedirect('usuarios/nuevo')
            ->assertSessionHasErrors(['email']);

        $this->assertEquals(0, User::count());
    }

    /** @test */
    function the_email_must_be_unique(){
        //$this->withoutExceptionHandling();

        factory(User::class)->create([
            'email'=> 'novo.esteban@gmail.com',
        ]);

        $this
            ->from('usuarios/nuevo')
            ->post('/usuarios', [
                'name'=> 'Esteban Novo',
                'email'=> 'novo.esteban@gmail.com',
                'password' => 'laravel'
            ])
            ->assertRedirect('usuarios/nuevo')
            ->assertSessionHasErrors(['email']);

        $this->assertEquals(1, User::count());
    }

    /** @test */
    function the_password_must_be_at_least_six_characters(){
        //$this->withoutExceptionHandling();

        $this
            ->from('usuarios/nuevo')
            ->post('/usuarios', [
                'name'=> 'Esteban Novo',
                'email'=> 'novo.esteban@gmail.com',
                'password' => 'lar'
            ])
            ->assertRedirect('usuarios/nuevo')
            ->assertSessionHasErrors(['password']);

        $this->assertEquals(0, User::count());
    }

    /** @test */
    function  it_loads_the_edit_user_page()
    {
        //$this->withoutExceptionHandling();
        $user = factory(User::class)->create([
            /*'name'=> 'Esteban Novo',
            'email'=> 'novo.esteban@gmail.com',
            'password' => 'laravel'*/
        ]);

        $this->get("/usuarios/{$user->id}/editar")
            ->assertStatus(200)
            ->assertViewIs('users.edit')
            ->assertSee('Editar usuario')
            ->assertViewHas('user', function($viewUser) use ($user){
                return $viewUser->id == $user->id;
            });
    }

    /** @test */
    function  it_updates_a_user()
    {
        $user = factory(User::class)->create();

        $this->withoutExceptionHandling();

        $this->put("/usuarios/{$user->id}", [
            'name'=> 'Esteban Novo',
            'email'=> 'novo.esteban@gmail.com',
            'password' => 'laravel'
        ])->assertRedirect("usuarios/{$user->id}");

        $this->assertCredentials([
            'name'=> 'Esteban Novo',
            'email'=> 'novo.esteban@gmail.com',
            'password' => 'laravel'
        ]);
    }

    /** @test */
    function  the_name_is_required_when_updating_a_user()
    {
        //$this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $this
            ->from("/usuarios/{$user->id}/editar")
            ->put("/usuarios/{$user->id}", [
            'name'=> '',
            'email'=> 'novo.esteban+2@gmail.com',
            'password' => 'laravel'
        ])
        ->assertRedirect("usuarios/{$user->id}/editar")
        ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('users', ['email'=>'novo.esteban+2@gmail.com']);
    }

    /** @test */
    function the_email_is_required_when_updating_a_user(){
        //$this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $this
            ->from("/usuarios/{$user->id}/editar")
            ->put("/usuarios/{$user->id}", [
                'name'=> 'Esteban Novo 2',
                'email'=> '',
                'password' => 'laravel'
            ])
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['name'=>'Esteban Novo 2']);
    }

    /** @test */
    function the_password_is_required_when_updating_a_user(){
        //$this->withoutExceptionHandling();
        $user = factory(User::class)->create();
        $this
            ->from("/usuarios/{$user->id}/editar")
            ->put("/usuarios/{$user->id}", [
                'name'=> 'Esteban Novo 2',
                'email'=> 'novo.esteban+3@gmail.com',
                'password' => ''
            ])
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['name'=>'Esteban Novo 2', 'email'=>'novo.esteban+3@gmail.com']);
    }

    /** @test */
    function the_email_must_be_valid_when_updating_a_user(){
        //$this->withoutExceptionHandling();
        $user = factory(User::class)->create();
        $this
            ->from("/usuarios/{$user->id}/editar")
            ->put("/usuarios/{$user->id}", [
                'name'=> 'Esteban Novo 2',
                'email'=> 'correo-no-valido',
                'password' => 'laravel'
            ])
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['name'=>'Esteban Novo 2']);
    }

    /** @test */
    function the_email_must_be_unique_when_updating_a_user(){
        //$this->withoutExceptionHandling();
        self::markTestIncomplete();
        return;

        $user = factory(User::class)->create([
            'email'=> 'novo.esteban+3@gmail.com'
        ]);
        $this
            ->from("/usuarios/{$user->id}/editar")
            ->put("/usuarios/{$user->id}", [
                'name'=> 'Esteban Novo 2',
                'email'=> 'novo.esteban+3@gmail.com',
                'password' => 'laravel'
            ])
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['name'=>'Esteban Novo 2']);
    }

    /** @test */
    function the_password_must_be_at_least_six_characters_when_updating_a_user(){
        //$this->withoutExceptionHandling();
        $user = factory(User::class)->create();
        $this
            ->from("/usuarios/{$user->id}/editar")
            ->put("/usuarios/{$user->id}", [
                'name'=> 'Esteban Novo 2',
                'email'=> 'novo.esteban+3@gmail.com',
                'password' => ''
            ])
            ->assertRedirect("usuarios/{$user->id}/editar")
            ->assertSessionHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['email'=>'novo.esteban+3@gmail.com']);
    }


}
