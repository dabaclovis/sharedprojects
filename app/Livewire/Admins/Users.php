<?php

namespace App\Livewire\Admins;

use App\Livewire\Forms\UsersForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'users ~ management service',
        'description' => 'This is the users page of my app.',
        'keywords' => 'users, my app',
    ]
)]
class Users extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public UsersForm $form;

    public bool $showForm = false;

    public string $search = '';

    public function boot(): void
    {
        $admin = Auth::user();

        abort_unless($admin instanceof User && $admin->role === 'admin' && $admin->status === 'active', 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->cancel();
        $this->showForm = true;
    }

    public function edit(User $user): void
    {
        $this->cancel();
        $this->form->setUser($user);
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->form->reset();
        $this->showForm = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        if (! $this->form->save()) {
            return;
        }

        Session::flash('users_message', $this->form->userId ? 'User updated.' : 'User created.');
        $this->cancel();
        $this->resetPage();
    }

    public function delete(User $user): void
    {
        if ($user->id === Auth::id()) {
            $this->addError('delete', 'You cannot delete your own account.');

            return;
        }

        $user->delete();
        if ($this->form->userId === $user->id) {
            $this->cancel();
        }
        $this->resetErrorBag('delete');
        $this->resetPage();
        Session::flash('users_message', 'User deleted.');
    }

    public function render(): View
    {
        $search = trim($this->search);

        return ViewFacade::file(resource_path('views/livewire/admins/users.blade.php'), [
            'users' => User::query()
                ->select(['id', 'name', 'username', 'email', 'role', 'status'])
                ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                }))
                ->orderByDesc('id')
                ->paginate(10),
        ]);
    }
}
