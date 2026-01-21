import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({
    alt = 'EncityCoffee Logo',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return <img src="/logo.svg" alt={alt} {...props} />;
}
