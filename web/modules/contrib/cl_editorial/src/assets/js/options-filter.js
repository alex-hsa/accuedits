(function(once) {

  class OptionsFilter {
    states = [];
    deprecatedMessage = 'The selected component is deprecated and will be removed. Evaluate migrating to a stable component.';

    initIndex(index = 0) {
      this.states[index] = {};
    }

    setContainer(container, index = 0) {
      this.states[index].container = container;
      this.states[index].components = Array.from(container.querySelectorAll('.js-form-type-radio'));
      var deprecationCheckbox = container.querySelector('input.deprecation-checkbox');
      this.states[index].deprecatedFilterValue = true;
      if (deprecationCheckbox !== null) {
        this.states[index].deprecatedFilterValue = container.querySelector(
          'input.deprecation-checkbox').checked;
      }
      this.states[index].infoLayer = container.querySelector('.currently-selected');
      this.states[index].infoLayer.hidden = true;
      this.states[index].warningLayer = container.querySelector('.warning');
      this.states[index].warningLayer.hidden = true;

      this.setDefaultComponentInDOM(index);
    }

    getContainer(index = 0) {
      return this.states[index].container;
    }

    getInfoLayer(index = 0) {
      return this.states[index].infoLayer;
    }

    setSearchboxValue(value, index = 0) {
      this.states[index].searchboxValue = value;
    }

    getSearchboxValue(index = 0) {
      return this.states[index].searchboxValue;
    }

    setDeprecatedFilterValue(value, index = 0) {
      this.states[index].deprecatedFilterValue = value;
    }

    getDeprecatedFilterValue(index = 0) {
      return this.states[index].deprecatedFilterValue;
    }

    setSelectedComponent(selectedComponent, index = 0) {
      this.states[index].selectedComponent = selectedComponent;
    }

    getSelectedComponent(index = 0) {
      return this.states[index].selectedComponent;
    }

    setDefaultComponentInDOM(index = 0) {
      const container = this.getContainer(index);
      const selectedComponent = container.querySelector('input[type="radio"]:checked');
      if (selectedComponent === null) {
        return null;
      }
      this.selectComponent(selectedComponent, index);
    }

    setSearchboxValueInDOM(value, index = 0) {
      const container = this.getContainer(index);
      const searchBox = container.querySelector('input.search-box');
      searchBox.value = value;
      this.setSearchboxValue(value);
    }

    selectComponent(selectedComponent, index = 0) {
      var componentWrapper = selectedComponent.closest('.js-form-type-radio');
      this.setSelectedComponent(componentWrapper, index);
      this.states[index].components.map(function(element) {
        element.classList.remove('form-type--radio__selected');
      });
      componentWrapper.classList.add('form-type--radio__selected');
      this.showWarningMessageIfNeeded(index);
      this.setSearchboxValueInDOM(componentWrapper.querySelector('.radio-details--machine-name').innerText);
    }

    showAllComponents(index = 0) {
      this.states[index].components.map(function(element) {
        element.hidden = false;
      });
    }

    showSelectedComponent(index = 0) {
      const selectedComponent = this.getSelectedComponent(index);
      if (selectedComponent) {
        this.getSelectedComponent(index).hidden = false;
      }
    }

    hideAllComponents(index = 0) {
      this.states[index].components.map(function(element) {
        element.hidden = true;
      });
    }

    hideAllComponentsBut(componentsToShow, index = 0) {
      this.hideAllComponents(index);
      for (const matchingElement of componentsToShow) {
        matchingElement.parentElement.parentElement.hidden = false;
      }
    }

    filterComponentsSearched(index = 0) {
      const container = this.getContainer(index);
      const searchboxValue = this.getSearchboxValue(index);
      const matchingComponents = Array.from(
        container.querySelectorAll('.radio-details--search')).
        filter(e => e.innerText.search(searchboxValue) !== -1);
      this.hideAllComponentsBut(matchingComponents, index);
    }

    isComponentDeprecated(component) {
      return component.innerText.search('deprecated') >= 0;
    }

    getDeprecatedComponents(index = 0) {
      return this.states[index].components.filter((component) => this.isComponentDeprecated(component));
    }

    filterComponentsDeprecated(index = 0) {
      var showDeprecated = this.getDeprecatedFilterValue(index);
      this.getDeprecatedComponents(index).map((component) => {
        if (!showDeprecated) {
          component.hidden = true;
        }
      });
    }

    showWarningMessageIfNeeded(index = 0) {
      var selectedComponent = this.getSelectedComponent(index);
      this.states[index].warningLayer.hidden = true;
      this.states[index].warningLayer.innerText = '';
      if (this.isComponentDeprecated(selectedComponent)) {
        this.states[index].warningLayer.hidden = false;
        this.states[index].warningLayer.innerText = this.deprecatedMessage;
      }
    }

    refreshComponents(index = 0) {
      this.showAllComponents(index);
      this.filterComponentsSearched(index);
      this.filterComponentsDeprecated(index);
      this.showSelectedComponent(index);
    }

    renderSelectedComponent(index = 0) {
      var info = this.getInfoLayer(index);
      var selectedComponent = this.getSelectedComponent(index);
      if (!selectedComponent) {
        return;
      }

      var id = selectedComponent.querySelector('input[type="radio"]').value;
      var name = selectedComponent.querySelector('.radio-details--human-name').innerText;
      var description = selectedComponent.querySelector('.radio-details--description').innerText;
      var status = selectedComponent.querySelector('.radio-details--status').innerText;
      var group = selectedComponent.querySelector('.radio-details--group').innerText;
      var documentation = selectedComponent.querySelector('.radio-details--documentation').innerHTML;
      const imgElement = selectedComponent.querySelector('.radio-details--image');
      var img = imgElement ? imgElement.outerHTML : '';
      info.innerHTML = Drupal.theme(
        'currentlySelectedComponent',
        id,
        name,
        description,
        status,
        group,
        documentation,
        img
      );
      info.hidden = false;
    }

  }

  const subscribeSearchboxToChanges = function(optionsFilter, index = 0) {
    const container = optionsFilter.getContainer(index);
    const searchBox = container.querySelector('input.search-box');
    if (searchBox === null) {
      return;
    }
    searchBox.addEventListener('input', function(event) {
      optionsFilter.setSearchboxValue(event.target.value, index);
      optionsFilter.refreshComponents(index);
    });
  };

  const includeResetButton = function(optionsFilter, index) {
    const container = optionsFilter.getContainer(index);
    const searchBox = container.querySelector('input.search-box');
    if (searchBox === null) {
      return;
    }
    var resetButton = document.createElement('button');
    resetButton.classList.add('reset-search-box');
    resetButton.innerText = '⨯';
    resetButton.title = 'Reset filter';
    resetButton.addEventListener('click', function(event) {
      event.preventDefault();
      container.querySelector('input.search-box').value = '';
      optionsFilter.setSearchboxValue('', index);
      optionsFilter.refreshComponents(index);
      event.target.blur();
    });

    searchBox.parentElement.insertBefore(resetButton, searchBox.nextSibling);
  };

  const subscribeDeprecatedSearchboxToChanges = function(optionsFilter, index = 0) {
    const container = optionsFilter.getContainer(index);
    const deprecationCheckbox = container.querySelector('input.deprecation-checkbox');
    if (deprecationCheckbox === null) {
      return;
    }
    deprecationCheckbox.addEventListener('change', function(event) {
      optionsFilter.setDeprecatedFilterValue(event.target.checked, index);
      optionsFilter.refreshComponents(index);
    });
  };

  const subscribeRadiosToChanges = function(optionsFilter, index = 0) {
    const container = optionsFilter.getContainer(index);
    var radios = once('radio-change-subscribed', 'input[type="radio"]', container);
    radios.map(function(radio) {
      radio.addEventListener('change', function(event) {
        optionsFilter.selectComponent(event.target, index);
        optionsFilter.refreshComponents(index);
        optionsFilter.renderSelectedComponent(index);
      });
    });
  };

  Drupal.behaviors.optionsFilter = {
    attach: (context, settings) => {
      once('options-filter', '.component--selector', context).map(function(container, index) {
        const optionsFilter = new OptionsFilter();
        optionsFilter.initIndex(index);
        optionsFilter.setContainer(container, index);
        subscribeSearchboxToChanges(optionsFilter, index);
        subscribeDeprecatedSearchboxToChanges(optionsFilter, index);
        subscribeRadiosToChanges(optionsFilter, index);
        includeResetButton(optionsFilter, index);
        optionsFilter.refreshComponents(index);
        if (optionsFilter.getSelectedComponent(index) !== undefined) {
          optionsFilter.renderSelectedComponent(index);
        }
      });
    },
  };

  Drupal.theme.currentlySelectedComponent = (
    id,
    name,
    description,
    status,
    group,
    documentation,
    img
  ) => `
    <summary>${Drupal.t('ℹ️ More information about <em>@name</em>',
    {'@name': name})}</summary>
    <p>${description}</p>
    <div class='image-table--wrapper'>
      <table>
        <tr><th>${Drupal.t('ID')}</th><td><code>${id}</code></td></tr>
        <tr><th>${Drupal.t('Status')}</th><td>${status}</td></tr>
        <tr><th>${Drupal.t('Group')}</th><td>${group}</td></tr>
      </table>
      <div class='currently-selected--image--wrapper${img
    ? ''
    : ' currently-selected--image--wrapper__empty'}'>
        ${img ? img : ''}
      </div>
    </div>
    <div class='currently-selected--readme'>${documentation}</div>
  `;
}(once));
