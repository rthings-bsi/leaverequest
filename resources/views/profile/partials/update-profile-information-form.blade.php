<section>
    <header class="mb-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">
            {{ __('Profile') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 max-w-2xl">
            {{ __('Update your public profile — make it yours with a photo, department and NIP.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div
            class="bg-gradient-to-br from-white to-slate-50 shadow-md rounded-2xl p-6 flex flex-col lg:flex-row gap-6 items-center">
            {{-- Avatar column --}}
            <div class="w-full lg:w-1/4 flex flex-col items-center text-center">
                <div id="avatarPreview" class="relative">
                    @php
                        $avatarUrl = $user->avatar_path ? asset('storage/' . $user->avatar_path) : null;
                        $initials = collect(explode(' ', trim($user->name ?? '')))
                            ->map(fn($p) => strtoupper(substr($p, 0, 1)))
                            ->join('');
                    @endphp

                    <div
                        class="w-32 h-32 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 overflow-hidden flex items-center justify-center text-white text-2xl font-semibold">
                        @if ($avatarUrl)
                            <img id="avatarImg" src="{{ $avatarUrl }}" alt="avatar"
                                class="w-full h-full object-cover" />
                        @else
                            <span id="avatarInitials">{{ $initials ?: 'U' }}</span>
                        @endif
                    </div>

                    <button type="button" id="changeAvatarBtn"
                        class="absolute -bottom-2 right-0 bg-white rounded-full p-2 shadow-md border">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-700" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536M9 11l6 6m0 0l3-3m-3 3V18a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2h6" />
                        </svg>
                    </button>

                </div>

                <div class="mt-4 flex items-center gap-3">
                    <label for="avatar"
                        class="cursor-pointer inline-flex items-center px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                        Change
                    </label>

                    <button type="button" id="removeAvatar"
                        class="inline-flex items-center px-3 py-2 bg-white border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        Remove
                    </button>
                </div>

                <input id="avatar" name="avatar" type="file" accept="image/*" class="hidden" />
                <input type="hidden" name="remove_avatar" id="remove_avatar" value="0" />

                <p class="mt-3 text-xs text-gray-500">PNG, JPG up to 5MB. Will be cropped to a circle.</p>
            </div>

            {{-- Fields column --}}
            <div class="w-full lg:w-3/4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            :value="old('name', $user->name)" required autofocus autocomplete="name" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                            :value="old('email', $user->email)" required autocomplete="username" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="nip" :value="__('NIP')" />
                        <x-text-input id="nip" name="nip" type="text" class="mt-1 block w-full"
                            :value="old('nip', $user->nip)" />
                        <x-input-error class="mt-2" :messages="$errors->get('nip')" />
                    </div>

                    <div>
                        <x-input-label for="department_id" :value="__('Department')" />
                        <select id="department_id" name="department_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($departments ?? collect() as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ (int) old('department_id', $user->department_id) === $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
                        <x-input-error class="mt-2" :messages="$errors->get('department')" />
                    </div>

                    <div>
                        <x-input-label for="contract_start_date" :value="__('Contract Start Date')" />
                        <x-text-input id="contract_start_date" name="contract_start_date" type="date"
                            class="mt-1 block w-full" :value="old(
                                'contract_start_date',
                                optional($user->contract_start_date)->format('Y-m-d'),
                            )" />
                        <x-input-error class="mt-2" :messages="$errors->get('contract_start_date')" />
                        <p class="text-xs text-gray-500 mt-1">Used to reset leave quota to 12 from the new contract
                            year.</p>
                    </div>

                    <div>
                        <x-input-label for="contract_end_date" :value="__('Contract End Date')" />
                        <x-text-input id="contract_end_date" name="contract_end_date" type="date"
                            class="mt-1 block w-full" :value="old('contract_end_date', optional($user->contract_end_date)->format('Y-m-d'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('contract_end_date')" />
                        <p class="text-xs text-gray-500 mt-1">Optional. Caps leave cycle at contract end.</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-4">
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>

                    @if (session('status') === 'profile-updated')
                        <p id="savedMsg" class="text-sm text-gray-600">{{ __('Saved.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <script>
        (function() {
            const avatarInput = document.getElementById('avatar');
            const avatarImg = document.getElementById('avatarImg');
            const avatarInitials = document.getElementById('avatarInitials');
            const removeBtn = document.getElementById('removeAvatar');
            const changeBtn = document.getElementById('changeAvatarBtn');
            const removeInput = document.getElementById('remove_avatar');

            if (changeBtn) {
                changeBtn.addEventListener('click', () => avatarInput.click());
            }

            if (avatarInput) {
                avatarInput.addEventListener('change', function(e) {
                    const file = this.files && this.files[0];
                    if (!file) return;
                    removeInput.value = '0';
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        if (avatarImg) {
                            avatarImg.src = ev.target.result;
                            avatarImg.style.display = '';
                        }
                        if (avatarInitials) avatarInitials.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    // clear preview and mark remove flag
                    if (avatarImg) {
                        avatarImg.src = '';
                        avatarImg.style.display = 'none';
                    }
                    if (avatarInitials) avatarInitials.style.display = '';
                    avatarInput.value = '';
                    removeInput.value = '1';
                });
            }
        })();
    </script>
</section>
