import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { InputError } from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import * as password from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const form = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(password.email.url());
    };

    return (
        <AuthLayout
            title="Reset your password"
            description="We'll email you a secure reset link"
        >
            <Head title="Forgot password" />

            {status && (
                <div className="bg-success/10 text-success rounded-md p-3 text-sm">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="username"
                        autoFocus
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                    <InputError message={form.errors.email} />
                </div>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    Email password reset link
                </Button>
            </form>

            <p className="text-muted-foreground text-center text-sm">
                <Link
                    href={login.url()}
                    className="text-foreground font-medium hover:underline"
                >
                    Back to log in
                </Link>
            </p>
        </AuthLayout>
    );
}
