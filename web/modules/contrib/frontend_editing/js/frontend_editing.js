/**
 * Implements frontend editing.
 */

(function (Drupal, once) {
  // Global variables
  let sidebarWidth = 30;
  let sidebarFullWidth = 70;

  // Remove all placeholders.
  const removePlaceholder = function () {
    // Delete all previews placeholders.
    const previewElements = document.querySelectorAll(
      '.frontend-editing--placeholder',
    );
    previewElements.forEach(function (previewElement) {
      previewElement.remove();
    });
    const activeEditingElements = document.querySelectorAll(
      '.frontend-editing--active-editing',
    );
    activeEditingElements.forEach(function (activeEditingElement) {
      activeEditingElement.classList.remove('frontend-editing--active-editing');
      activeEditingElement.classList.remove('frontend-editing--outline');
    });
  };

  // Callback for click function on an editable element.
  const editingClick = function (e) {
    e.preventDefault();
    // Setup container.
    // Frontend-editing sidebar and full widths.
    const wideClassWidth = `${sidebarFullWidth}%`;
    const sidebarClassWidth = `${sidebarWidth}%`;

    let editContainer = document.getElementById('editing-container');
    if (!editContainer) {
      editContainer = document.createElement('div');
      editContainer.id = 'editing-container';
      editContainer.classList.add(
        'editing-container',
        'editing-container--loading',
      );
      document.body.append(editContainer);
      editContainer.style.width = sidebarClassWidth;
    } else {
      editContainer.innerHTML = '';
    }
    // Setup width toggle button.
    const editWideClass = 'editing-container--wide';
    const widthToggle = document.createElement('button');
    widthToggle.type = 'button';
    widthToggle.className = 'editing-container__toggle';
    widthToggle.addEventListener('click', function (e) {
      if (editContainer.classList.contains(editWideClass)) {
        editContainer.classList.remove(editWideClass);
        editContainer.style.width = sidebarClassWidth;
      } else {
        editContainer.classList.add(editWideClass);
        editContainer.style.width = wideClassWidth;
      }
    });
    // Setup close button.
    const editClose = document.createElement('button');
    editClose.className = 'editing-container__close';
    editClose.type = 'button';
    editClose.addEventListener('click', function (e) {
      editContainer.remove();
      removePlaceholder();
    });

    const actions = document.createElement("div");
    actions.classList.add('editing-container__actions');
    actions.appendChild(widthToggle);
    actions.appendChild(editClose);

    // Populate container.
    editContainer.appendChild(actions);

    // Load editing iFrame and append.
    const iframe = document.createElement('iframe');
    iframe.onload = function () {
      editContainer.classList.remove('editing-container--loading');
    };
    editContainer.appendChild(iframe);
    iframe.src = e.target.href;

    removePlaceholder();
    const entityContainer = e.target.closest('.frontend-editing');
    if (e.target.classList.contains('frontend-editing__action--edit')) {
      entityContainer.classList.add('frontend-editing--active-editing');
    }
    // Add placeholder for preview if add actions were triggered.
    if (
      e.target.classList.contains('frontend-editing__action--after') ||
      e.target.classList.contains('frontend-editing__action--before')
    ) {
      if (entityContainer.dataset.preview) {
        const placeholder = document.createElement(entityContainer.tagName);
        placeholder.innerHTML = Drupal.t(
          'Here will be your new content. Click "Preview" button in the form to see it.',
        );
        placeholder.classList.add('frontend-editing');
        placeholder.classList.add('frontend-editing--placeholder');
        if (e.target.classList.contains('frontend-editing__action--after')) {
          placeholder.dataset.preview = `${entityContainer.dataset.preview}-after`;
          entityContainer.parentNode.insertBefore(
            placeholder,
            entityContainer.nextSibling,
          );
        }
        if (e.target.classList.contains('frontend-editing__action--before')) {
          placeholder.dataset.preview = `${entityContainer.dataset.preview}-before`;
          entityContainer.parentNode.insertBefore(
            placeholder,
            entityContainer.previousSibling,
          );
        }
      }
    }
  };

  /**
   * Ajax command feReloadPage.
   *
   * Reloads the page in case ajax content update failed.
   *
   * @param ajax
   * @param response
   * @param status
   */
  Drupal.AjaxCommands.prototype.feReloadPage = function (
    ajax,
    response,
    status,
  ) {
    if (status === 'success') {
      // Reload the page.
      window.location.reload();
    }
  };

  /**
   * Add callback for sidebar tray and add listeners to actions.
   */
  Drupal.behaviors.frontendEditing = {
    attach(context, settings) {
      sidebarFullWidth = settings.frontend_editing.full_width;
      sidebarWidth = settings.frontend_editing.sidebar_width;
      const actionsElements = once(
        'frontend-editing-processed',
        '.frontend-editing-actions',
        context,
      );
      // Find all elements with frontend editing action buttons and attach event listener.
      actionsElements.forEach(function (actionsElement) {
        actionsElement.addEventListener('mouseover', function () {
          const container = actionsElement.closest('.frontend-editing');
          if (container) {
            container.classList.add('frontend-editing--outline');
          }
        });

        actionsElement.addEventListener('mouseout', function () {
          const container = actionsElement.closest('.frontend-editing');
          if (
            container &&
            !container.classList.contains('frontend-editing--active-editing')
          ) {
            container.classList.remove('frontend-editing--outline');
          }
        });

        actionsElement
          .querySelectorAll('.frontend-editing__action')
          .forEach(function (editingElement, i) {
            if (
              editingElement.classList.contains('frontend-editing-open-sidebar')
            ) {
              // Add selector for auto update content if exists.
              if (actionsElement.dataset.entityType !== 'paragraph') {
                const fieldWrapper = actionsElement.closest(
                  '.frontend-editing-field-wrapper',
                );
                if (fieldWrapper && fieldWrapper.dataset.frontendEditing) {
                  editingElement.href = `${editingElement.href}?selector=${fieldWrapper.dataset.frontendEditing}`;
                }
              }
              editingElement.addEventListener('click', editingClick);
            }
          });
      });
    },
  };
})(Drupal, once);
