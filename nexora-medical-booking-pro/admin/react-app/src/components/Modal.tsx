import React from 'react';

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
}

export const Modal: React.FC<ModalProps> = ({ isOpen, onClose, title, children, footer }) => {
  if (!isOpen) return null;

  return (
    <div className="nmbp-modal-backdrop" onClick={onClose}>
      <div className="nmbp-modal-window" onClick={(e) => e.stopPropagation()}>
        <div className="nmbp-modal-header">
          <h3>{title}</h3>
          <button className="nmbp-modal-close" onClick={onClose}>&times;</button>
        </div>
        <div className="nmbp-modal-body">
          {children}
        </div>
        {footer && <div className="nmbp-modal-footer">{footer}</div>}
      </div>
    </div>
  );
};

