import { useCallback, useState } from 'react';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
  ImageIcon,
  Upload,
  X,
  Maximize2,
  AlertCircle,
} from 'lucide-react';

interface ImageUploaderProps {
  value: File | null;
  previewUrl?: string | null;
  onChange: (file: File | null) => void;
  onRemove?: () => void; // Called when user wants to remove/delete the image (for edit forms)
  error?: string;
  label?: string;
  accept?: string;
  maxSizeMB?: number;
  className?: string;
}

export function ImageUploader({
  value,
  previewUrl,
  onChange,
  onRemove,
  error,
  label = 'Gambar Produk',
  accept = 'image/png,image/jpeg,image/jpg,image/webp',
  maxSizeMB = 2,
  className,
}: ImageUploaderProps) {
  const [isDragging, setIsDragging] = useState(false);
  const [preview, setPreview] = useState<string | null>(null);
  const [isEnlarged, setIsEnlarged] = useState(false);
  const [validationError, setValidationError] = useState<string | null>(null);

  const maxSizeBytes = maxSizeMB * 1024 * 1024;

  const validateFile = useCallback((file: File): string | null => {
    if (!accept.split(',').some(type => file.type === type.trim() || file.type.startsWith(type.trim().split('/')[0] + '/'))) {
      return `Format file tidak valid. Gunakan: ${accept}`;
    }
    if (file.size > maxSizeBytes) {
      return `Ukuran file maksimal ${maxSizeMB}MB`;
    }
    return null;
  }, [accept, maxSizeBytes, maxSizeMB]);

  const handleFile = useCallback((file: File) => {
    const error = validateFile(file);
    if (error) {
      setValidationError(error);
      return;
    }
    setValidationError(null);
    onChange(file);
    
    // Create preview
    const reader = new FileReader();
    reader.onloadend = () => {
      setPreview(reader.result as string);
    };
    reader.readAsDataURL(file);
  }, [onChange, validateFile]);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    
    const file = e.dataTransfer.files[0];
    if (file) {
      handleFile(file);
    }
  }, [handleFile]);

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  }, []);

  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0] ?? null;
    if (file) {
      handleFile(file);
    }
  }, [handleFile]);

  const handleRemove = useCallback(() => {
    onChange(null);
    setPreview(null);
    setValidationError(null);
    if (onRemove) {
      onRemove();
    }
  }, [onChange, onRemove]);

  // Use preview URL from props (existing image) or local preview (new upload)
  const displayPreviewUrl = preview || previewUrl || null;

  const displayError = validationError || error || null;

  return (
    <div className={cn('space-y-2', className)}>
      {label && (
        <Label className="text-sm font-medium">
          {label}
        </Label>
      )}
      
      <div className="space-y-3">
        {/* Current/New Image Preview */}
        {displayPreviewUrl && (
          <div className="relative group">
            <div 
              className="relative overflow-hidden rounded-lg border bg-muted"
              style={{ aspectRatio: '4/3' }}
            >
              <img
                src={displayPreviewUrl}
                alt="Preview"
                className="h-full w-full object-cover transition-transform group-hover:scale-105"
              />
              
              {/* Hover overlay with actions */}
              <div className="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                <Button
                  type="button"
                  variant="secondary"
                  size="icon"
                  onClick={() => setIsEnlarged(true)}
                  className="h-8 w-8"
                >
                  <Maximize2 className="h-4 w-4" />
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  size="icon"
                  onClick={handleRemove}
                  className="h-8 w-8"
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            </div>
            
            {/* Remove button below on mobile */}
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={handleRemove}
              className="mt-2 w-full text-destructive hover:text-destructive"
            >
              <X className="h-4 w-4 mr-1" />
              Hapus Gambar
            </Button>
          </div>
        )}

        {/* Upload Area - Only show if no image */}
        {!displayPreviewUrl && (
          <div
            className={cn(
              'relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 transition-colors cursor-pointer',
              isDragging 
                ? 'border-primary bg-primary/5' 
                : 'border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/50',
              displayError && 'border-destructive bg-destructive/5'
            )}
            onDrop={handleDrop}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onClick={() => document.getElementById('image-upload-input')?.click()}
          >
            <input
              id="image-upload-input"
              type="file"
              accept={accept}
              onChange={handleInputChange}
              className="hidden"
            />
            
            <div className="flex flex-col items-center gap-3 text-center">
              <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                {displayError ? (
                  <AlertCircle className="h-6 w-6 text-destructive" />
                ) : (
                  <Upload className="h-6 w-6 text-muted-foreground" />
                )}
              </div>
              
              <div className="space-y-1">
                <p className="text-sm font-medium">
                  {isDragging ? 'Lepaskan file di sini' : 'Klik atau drag & drop untuk upload'}
                </p>
                <p className="text-xs text-muted-foreground">
                  {accept.split(',').map(t => t.trim().split('/')[1]?.toUpperCase()).join(', ')} • Maks {maxSizeMB}MB
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Replace image button - show when there's an image */}
        {displayPreviewUrl && (
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="w-full"
            onClick={() => document.getElementById('image-upload-input-replace')?.click()}
          >
            <Upload className="h-4 w-4 mr-1" />
            Ganti Gambar
            <input
              id="image-upload-input-replace"
              type="file"
              accept={accept}
              onChange={handleInputChange}
              className="hidden"
            />
          </Button>
        )}

        {/* Error message */}
        {displayError && (
          <div className="flex items-center gap-2 text-sm text-destructive">
            <AlertCircle className="h-4 w-4" />
            {displayError}
          </div>
        )}
      </div>

      {/* Enlarged Image Dialog */}
      <Dialog open={isEnlarged} onOpenChange={setIsEnlarged}>
        <DialogContent className="max-w-4xl p-0 border-0 bg-transparent shadow-none">
          <div className="relative flex items-center justify-center">
            <img
              src={displayPreviewUrl || ''}
              alt="Preview enlarged"
              className="max-h-[80vh] max-w-full rounded-lg object-contain"
              onClick={() => setIsEnlarged(false)}
            />
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}

// Simple image preview component for read-only display
interface ImagePreviewProps {
  src?: string | null;
  alt?: string;
  className?: string;
}

export function ImagePreview({
  src,
  alt = 'Image',
  className,
}: ImagePreviewProps) {
  const [isEnlarged, setIsEnlarged] = useState(false);
  const [hasError, setHasError] = useState(false);

  if (!src || hasError) {
    return (
      <div className={cn(
        'flex items-center justify-center rounded-lg border bg-muted',
        className
      )}>
        <ImageIcon className="h-8 w-8 text-muted-foreground" />
      </div>
    );
  }

  return (
    <>
      <div 
        className={cn(
          'relative overflow-hidden rounded-lg border cursor-pointer transition-transform hover:scale-105',
          className
        )}
        onClick={() => setIsEnlarged(true)}
      >
        <img
          src={src}
          alt={alt}
          className="h-full w-full object-cover"
          onError={() => setHasError(true)}
        />
        <div className="absolute inset-0 flex items-center justify-center bg-black/0 hover:bg-black/20 transition-colors">
          <Maximize2 className="h-6 w-6 text-white opacity-0 hover:opacity-100 transition-opacity" />
        </div>
      </div>
      
      <Dialog open={isEnlarged} onOpenChange={setIsEnlarged}>
        <DialogContent className="max-w-4xl p-0 border-0 bg-transparent shadow-none">
          <div className="relative flex items-center justify-center">
            <img
              src={src}
              alt={alt}
              className="max-h-[80vh] max-w-full rounded-lg object-contain"
              onClick={() => setIsEnlarged(false)}
            />
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
