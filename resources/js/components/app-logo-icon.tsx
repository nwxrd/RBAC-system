import { useState } from 'react';
import { usePage } from '@inertiajs/react';

type BrandPage = {
    name?: string;
    brand?: {
        logo?: string;
    };
};

export default function AppLogoIcon({ className }: { className?: string }) {
    const { name = '', brand } = usePage<BrandPage>().props;
    const [failed, setFailed] = useState(false);
    const initial = name.trim().charAt(0).toUpperCase();

    if (!brand?.logo || failed) {
        return (
            <span
                className={`inline-flex items-center justify-center text-xs font-semibold leading-none ${className ?? ''}`}
            >
                {initial}
            </span>
        );
    }

    return (
        <img
            src={brand.logo}
            alt=""
            className={`object-contain ${className ?? ''}`}
            onError={() => setFailed(true)}
        />
    );
}
