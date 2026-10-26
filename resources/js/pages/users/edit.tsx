import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { index, update } from '@/actions/App/Http/Controllers/UserController';
import AlertError from '@/components/alert-error';
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

type UserRole = {
    id: number;
    name: string;
    is_system: boolean;
};

type UserPayload = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: UserRole[];
};

export default function Edit({
    user,
    roles,
}: {
    user: UserPayload;
    roles: AssignableRole[];
}) {
    const page = usePage<{
        auth: { user: { id: number } | null };
    }>();
    const locked =
        page.props.auth.user?.id === user.id ||
        user.roles.some((role) => role.is_system);
    const assigned = new Set(
        user.roles.filter((role) => !role.is_system).map((role) => role.id),
    );
    const [active, setActive] = useState(user.is_active);

    return (
        <>
            <Head title="Editar usuario" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Editar usuario"
                    description="Nombre, correo, contraseña y roles"
                />

                <Card>
                    <CardContent>
                        <Form {...update.form(user.id)} className="space-y-6">
                            {({ errors, processing }) => (
                                <>
                                    {errors.error && (
                                        <AlertError
                                            title="No se pudo completar la operación"
                                            errors={[errors.error]}
                                        />
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nombre</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={user.name}
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Correo</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            defaultValue={user.email}
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
                                                    defaultChecked={assigned.has(
                                                        role.id,
                                                    )}
                                                />
                                                <Label
                                                    htmlFor={`role-${role.id}`}
                                                >
                                                    {role.name}
                                                </Label>
                                            </div>
                                        ))}
                                        <InputError message={errors.roles} />
                                    </div>

                                    <div className="flex items-center gap-2">
                                        {locked ? (
                                            <input
                                                type="hidden"
                                                name="is_active"
                                                value="1"
                                            />
                                        ) : (
                                            <input
                                                type="hidden"
                                                name="is_active"
                                                value="0"
                                            />
                                        )}
                                        <Checkbox
                                            id="is_active"
                                            name={
                                                locked ? undefined : 'is_active'
                                            }
                                            value="1"
                                            checked={locked ? true : active}
                                            disabled={locked}
                                            onCheckedChange={(checked) =>
                                                setActive(checked === true)
                                            }
                                        />
                                        <Label htmlFor="is_active">
                                            Cuenta activa
                                        </Label>
                                        <InputError message={errors.is_active} />
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

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Usuarios',
            href: index.url(),
        },
        {
            title: 'Editar',
            href: index.url(),
        },
    ],
};
