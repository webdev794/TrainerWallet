import { Head, Link, useForm } from '@inertiajs/react';
import { Dumbbell, User } from 'lucide-react';
import { type FormEvent } from 'react';

import { InputError } from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';

const roles = [
    {
        value: 'trainer',
        label: "I'm a trainer",
        hint: 'Send invoices and collect payments',
        icon: Dumbbell,
    },
    {
        value: 'client',
        label: "I'm a client",
        hint: 'View and pay my trainer’s invoices',
        icon: User,
    },
] as const;

export default function Register() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'trainer',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(register.url(), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    const isClient = form.data.role === 'client';

    return (
        <AuthLayout
            title="Create your account"
            description={
                isClient
                    ? 'Access your client portal'
                    : 'No credit card required'
            }
        >
            <Head title="Create account" />

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label>I want to…</Label>
                    <div className="grid grid-cols-2 gap-2">
                        {roles.map((role) => (
                            <button
                                key={role.value}
                                type="button"
                                onClick={() => form.setData('role', role.value)}
                                className={cn(
                                    'focus-visible:ring-ring flex flex-col items-start gap-1 rounded-md border p-3 text-left transition-colors focus-visible:ring-2 focus-visible:outline-none',
                                    form.data.role === role.value
                                        ? 'border-primary bg-accent'
                                        : 'border-input hover:bg-muted/60',
                                )}
                            >
                                <role.icon className="text-primary size-4" />
                                <span className="text-sm font-medium">
                                    {role.label}
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    {role.hint}
                                </span>
                            </button>
                        ))}
                    </div>
                    <InputError message={form.errors.role} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="name">Your name</Label>
                    <Input
                        id="name"
                        autoComplete="name"
                        autoFocus
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                    <InputError message={form.errors.name} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="username"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                    />
                    <InputError message={form.errors.email} />
                    {isClient && (
                        <p className="text-muted-foreground text-xs">
                            Use the same email your trainer has on file so your
                            invoices link automatically.
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                    />
                    <InputError message={form.errors.password} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">
                        Confirm password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(e) =>
                            form.setData(
                                'password_confirmation',
                                e.target.value,
                            )
                        }
                    />
                    <InputError message={form.errors.password_confirmation} />
                </div>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    {isClient ? 'Create account' : 'Start free trial'}
                </Button>
            </form>

            <p className="text-muted-foreground text-center text-sm">
                Already have an account?{' '}
                <Link
                    href={login.url()}
                    className="text-foreground font-medium hover:underline"
                >
                    Log in
                </Link>
            </p>
        </AuthLayout>
    );
}
