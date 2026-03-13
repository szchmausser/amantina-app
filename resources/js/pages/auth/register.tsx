import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    const [isTransfer, setIsTransfer] = useState(false);

    return (
        <AuthLayout
            title="Crear cuenta"
            description="Ingresa tus datos para registrarte en el sistema"
        >
            <Head title="Registro" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">

                            {/* Cédula */}
                            <div className="grid gap-2">
                                <Label htmlFor="cedula">Cédula</Label>
                                <Input
                                    id="cedula"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    name="cedula"
                                    placeholder="Número de cédula"
                                />
                                <InputError message={errors.cedula} />
                            </div>

                            {/* Nombre */}
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre completo</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Nombre completo"
                                />
                                <InputError message={errors.name} />
                            </div>

                            {/* Correo */}
                            <div className="grid gap-2">
                                <Label htmlFor="email">Correo electrónico</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={3}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="correo@ejemplo.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {/* Teléfono */}
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Teléfono</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    required
                                    tabIndex={4}
                                    name="phone"
                                    placeholder="Número de teléfono"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            {/* Dirección */}
                            <div className="grid gap-2">
                                <Label htmlFor="address">Dirección de residencia</Label>
                                <Input
                                    id="address"
                                    type="text"
                                    required
                                    tabIndex={5}
                                    name="address"
                                    placeholder="Dirección completa"
                                />
                                <InputError message={errors.address} />
                            </div>

                            {/* ¿Eres transferido? */}
                            <div className="grid gap-2">
                                <Label>¿Eres estudiante transferido de otra institución?</Label>
                                <div className="flex gap-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="is_transfer"
                                            value="0"
                                            defaultChecked
                                            tabIndex={6}
                                            onChange={() => setIsTransfer(false)}
                                        />
                                        No
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="is_transfer"
                                            value="1"
                                            tabIndex={7}
                                            onChange={() => setIsTransfer(true)}
                                        />
                                        Sí
                                    </label>
                                </div>
                                <InputError message={errors.is_transfer} />
                            </div>

                            {/* Institución de origen — solo si es transferido */}
                            {isTransfer && (
                                <div className="grid gap-2">
                                    <Label htmlFor="institution_origin">Institución de origen</Label>
                                    <Input
                                        id="institution_origin"
                                        type="text"
                                        required
                                        tabIndex={8}
                                        name="institution_origin"
                                        placeholder="Nombre de la institución anterior"
                                    />
                                    <InputError message={errors.institution_origin} />
                                </div>
                            )}

                            {/* Contraseña */}
                            <div className="grid gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={9}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Contraseña"
                                />
                                <InputError message={errors.password} />
                            </div>

                            {/* Confirmar contraseña */}
                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={10}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirmar contraseña"
                                />
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={11}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Crear cuenta
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿Ya tienes cuenta?{' '}
                            <TextLink href={login()} tabIndex={12}>
                                Iniciar sesión
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}