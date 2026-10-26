import { Form, Head, Link } from '@inertiajs/react';
import { create, index, store } from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AssignableRole = {
    id: number;
    name: string;
};

export default function Create({ roles }: { roles: AssignableRole[] }) {
    return (
        <>
            <Head title="Crear usuario" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Crear usuario"
                    description="La cuenta nace activa y con el correo verificado"
                />

                <Card>
                    <CardContent>
                        <Form {...store.form()} className="space-y-6">
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nombre</Label>
                                        <Input id="name" name="name" required />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Correo</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            required
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Contraseña
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            Confirmar contraseña
                                        </Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                        />
                                    </div>

                                    <div className="grid gap-3">
                                        <Label>Roles</Label>
                                        {roles.map((role) => (
                                            <div
                                                key={role.id}
                                                className="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id={`role-${role.id}`}
                                                    name="roles[]"
                                                    value={String(role.id)}
                                                />
                                                <Label htmlFor={`role-${role.id}`}>
                                                    {role.name}
                                                </Label>
                                            </div>
                                        ))}
                                        <InputError message={errors.roles} />
                                    </div>

                                    <div className="flex gap-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Guardar
                                        </Button>
                                        <Button variant="secondary" asChild>
                                            <Link href={index.url()}>
                                                Cancelar
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        {
            title: 'Usuarios',
            href: index.url(),
        },
        {
            title: 'Crear',
            href: create.url(),
        },
    ],
};
