import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { InputError } from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { login, register } from '@/routes';
import * as password from '@/routes/password';

export default function Login({
    canResetPassword = true,
    status,
}: {
    canResetPassword?: boolean;
    status?: string;
}) {
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(login.url(), {
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Welcome back"
            description="Log in to your CoachPay account"
        >
            <Head title="Log in" />

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

                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="password">Password</Label>
                        {canResetPassword && (
                            <Link
                                href={password.request.url()}
                                className="text-muted-foreground hover:text-foreground text-sm"
                            >
                                Forgot password?
                            </Link>
                        )}
                    </div>
                    <Input
                        id="password"
                        type="password"
                        autoComplete="current-password"
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                    />
                    <InputError message={form.errors.password} />
                </div>

                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.remember}
                        onCheckedChange={(checked) =>
                            form.setData('remember', checked === true)
                        }
                    />
                    Remember me
                </label>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    Log in
                </Button>
            </form>

            <p className="text-muted-foreground text-center text-sm">
                New to CoachPay?{' '}
                <Link
                    href={register.url()}
                    className="text-foreground font-medium hover:underline"
                >
                    Start a free trial
                </Link>
            </p>
        </AuthLayout>
    );
}
