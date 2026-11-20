<x-layout title="Log in">
    <div style="max-width: 420px; margin: 24px auto">
        <h1>BizDesk</h1>
        <p class="muted">Sign in to see your sales, stock and profit. Demo: <code>owner@bizdesk.test</code> / <code>password</code></p>
        <form method="POST" action="{{ url('/login') }}" class="panel">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" @class(['invalid' => $errors->has('email')])>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password">
            </div>
            <div class="field">
                <label style="display:flex; gap:8px; align-items:center; font-weight:500">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
            </div>
            <button class="btn btn-primary" style="width:100%; justify-content:center">Log in</button>
        </form>
    </div>
</x-layout>
